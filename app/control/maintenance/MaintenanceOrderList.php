<?php
/**
 * MaintenanceOrderList
 * Lista de Ordens de Serviço com Segurança (RBAC)
 */
class MaintenanceOrderList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'med_maintenance';
    private static $activeRecord = 'MaintenanceOrder';
    private static $primaryKey = 'id';
    private static $formName = 'formList_MaintenanceOrder';

    public function __construct()
    {
        parent::__construct();

        // Cria o formulário de busca
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('Ordens de Serviço');

        $title = new TEntry('title');
        $status = new TCombo('status');
        $status->addItems([
            'ABERTA' => 'Aberta',
            'EM ANDAMENTO' => 'Em Andamento',
            'FECHADA' => 'Fechada'
        ]);

        $this->form->addFields( [new TLabel('Título')], [$title] );
        $this->form->addFields( [new TLabel('Status')], [$status] );

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addAction('Nova OS', new TAction(['MaintenanceOrderForm', 'onEdit']), 'fa:plus green');

        // Cria a Datagrid (Tabela)
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        $this->datagrid->datatable = 'true'; // Habilita estilo responsivo

        // Cria as colunas
        $col_id = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_asset = new TDataGridColumn('{asset->name}', 'Equipamento', 'left', '30%');
        $col_tech = new TDataGridColumn('{technician->name}', 'Técnico', 'left', '30%');
        $col_status = new TDataGridColumn('status', 'Status', 'center', '20%');
        $col_priority = new TDataGridColumn('priority', 'Prioridade', 'center', '10%');

        // Transformador de Status (Cores)
        $col_status->setTransformer(function($value) {
            $class = ($value == 'FECHADA') ? 'success' : (($value == 'ABERTA') ? 'danger' : 'warning');
            return "<span class='badge bg-{$class}'>{$value}</span>";
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_asset);
        $this->datagrid->addColumn($col_tech);
        $this->datagrid->addColumn($col_status);
        $this->datagrid->addColumn($col_priority);

        // Ações da linha (Editar/Excluir)
        $action_edit = new TDataGridAction(['MaintenanceOrderForm', 'onEdit']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fa:edit blue');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);

        $action_del = new TDataGridAction([$this, 'onDelete']);
        $action_del->setLabel('Excluir');
        $action_del->setImage('fa:trash red');
        $action_del->setField('id');

        $action_pdf = new TDataGridAction(['MaintenanceOrderDocument', 'onGenerate']);
        $action_pdf->setLabel('Imprimir OS');
        $action_pdf->setImage('fas:print gray'); // Ícone de impressora
        $action_pdf->setField('id'); // Passa o ID como parâmetro
        $this->datagrid->addAction($action_pdf);
        
        $this->datagrid->addAction($action_del);

        // ✅ A CORREÇÃO ESTÁ AQUI: Cria o modelo da grid
        $this->datagrid->createModel();

        // Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        // Container
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add($this->datagrid);
        $vbox->add($this->pageNavigation);

        parent::add($vbox);
    }

    /**
     * Aplica a lógica de Segurança: Admin vê tudo, Técnico vê só o dele
     */
    private function applySystemSecurity($criteria)
    {
        // 1. Descobre quem está logado
        $logged_user_id = TSession::getValue('userid');
        
        // 2. Verifica se é ADMIN (Grupo 1)
        TTransaction::open('permission');
        $is_admin = false;
        // Carrega os grupos do usuário
        $user_groups = SystemUserGroup::where('system_user_id', '=', $logged_user_id)->load();
        foreach ($user_groups as $group) {
            if ($group->system_group_id == 1) { // 1 é o padrão para Admin
                $is_admin = true;
            }
        }
        TTransaction::close();

        // 3. Se NÃO for Admin, aplica o filtro
        if (!$is_admin) {
            TTransaction::open('med_maintenance');
            // Busca se esse usuário é um Técnico
            $technician = Technician::where('system_user_id', '=', $logged_user_id)->first();
            TTransaction::close();

            if ($technician) {
                // SUCESSO: É um técnico. Filtra pelo ID dele.
                $criteria->add(new TFilter('technician_id', '=', $technician->id));
            } else {
                // ERRO: Usuário comum tentando acessar. Bloqueia tudo.
                $criteria->add(new TFilter('id', '<', 0));
                new TMessage('error', 'ATENÇÃO: Seu usuário não está vinculado a nenhum cadastro de Técnico.');
            }
        }
    }

    public function onReload($param = NULL)
    {
        try
        {
            TTransaction::open(self::$database);

            $repository = new TRepository(self::$activeRecord);
            
            $criteria = new TCriteria;
            
            if ($this->filter_criteria) {
                $criteria = clone $this->filter_criteria;
            }

            // Aplica a segurança
            $this->applySystemSecurity($criteria);

            $limit = 10;
            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            $objects = $repository->load($criteria, FALSE);
            
            $this->datagrid->clear();
            if ($objects)
            {
                foreach ($objects as $object)
                {
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $count = $repository->count($criteria);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onSearch()
    {
        $data = $this->form->getData();
        $this->filter_criteria = new TCriteria;

        if ($data->title) {
            $this->filter_criteria->add(new TFilter('title', 'like', "%{$data->title}%"));
        }
        if ($data->status) {
            $this->filter_criteria->add(new TFilter('status', '=', $data->status));
        }

        $this->form->setData($data);
        $this->onReload();
    }

    public function onDelete($param)
    {
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param);
        new TQuestion('Deseja realmente excluir?', $action);
    }

    public function Delete($param)
    {
        try
        {
            $key = $param['id'];
            TTransaction::open(self::$database);
            $object = new MaintenanceOrder($key);
            $object->delete();
            TTransaction::close();
            $this->onReload();
            new TMessage('info', 'Registro excluído com sucesso');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function show()
    {
        if (!$this->loaded)
        {
            $this->onReload();
        }
        parent::show();
    }
}