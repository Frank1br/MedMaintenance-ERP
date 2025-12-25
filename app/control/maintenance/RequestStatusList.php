<?php
/**
 * RequestStatusList
 * Perfil Médico: Acompanhamento de Chamados (Versão Compatível - Sem setPlaceholder)
 */
class RequestStatusList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'med_maintenance';
    private static $activeRecord = 'MaintenanceOrder';
    private static $primaryKey = 'id';
    private static $formName = 'formList_RequestStatus';

    public function __construct()
    {
        parent::__construct();
        $this->setTargetContainer('adianti_div_content');

        // 1. FORMULÁRIO DE BUSCA (SIMPLIFICADO)
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('Acompanhar Solicitações');

        $title = new TEntry('title');
        // Linha removida para corrigir erro de versão:
        // $title->setPlaceholder('Buscar por equipamento...');
        
        $this->form->addFields( [new TLabel('Buscar:')], [$title] );

        $this->form->addAction('Pesquisar', new TAction([$this, 'onSearch']), 'fa:search blue');

        // 2. DATAGRID (TABELA)
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        $this->datagrid->datatable = 'true'; 

        // 3. AÇÕES (SOMENTE VISUALIZAÇÃO)
        $action_view = new TDataGridAction([$this, 'onView']);
        $action_view->setLabel('Ver Detalhes');
        $action_view->setImage('fa:search-plus gray');
        $action_view->setField(self::$primaryKey);

        $this->datagrid->addAction($action_view);

        // 4. COLUNAS
        $col_id = new TDataGridColumn('id', 'Chamado', 'center', '10%');
        $col_asset = new TDataGridColumn('{asset->name}', 'Equipamento', 'left', '30%');
        
        // Verifica se created_at existe no banco, caso contrário pode comentar essa linha
        $col_date = new TDataGridColumn('created_at', 'Aberto em', 'center', '20%'); 
        
        $col_status = new TDataGridColumn('status', 'Situação Atual', 'center', '20%');
        $col_tech = new TDataGridColumn('{technician->name}', 'Técnico', 'left', '20%');

        // Formatação visual do Status
        $col_status->setTransformer(function($value) {
            $class = ($value == 'FECHADA') ? 'success' : (($value == 'ABERTA') ? 'danger' : 'warning');
            $label = ($value == 'ABERTA') ? 'AGUARDANDO' : $value; 
            return "<span class='label label-{$class}' style='width:100%; display:block'>{$label}</span>";
        });
        
        // Tratamento para quando não tem técnico ainda
        $col_tech->setTransformer(function($value, $object) {
            return $object->technician ? $object->technician->name : '<span style="color:silver; font-style:italic">Não atribuído</span>';
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_asset);
        
        // Se sua tabela MaintenanceOrder não tiver created_at, comente a linha abaixo:
        // $this->datagrid->addColumn($col_date); 
        
        $this->datagrid->addColumn($col_status);
        $this->datagrid->addColumn($col_tech);

        $this->datagrid->createModel();

        // 5. NAVEGAÇÃO
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add($this->datagrid);
        $vbox->add($this->pageNavigation);

        parent::add($vbox);
    }

    /**
     * Ação de Visualizar Detalhes
     */
    public function onView($param)
    {
        try {
            TTransaction::open(self::$database);
            $object = new MaintenanceOrder($param['id']);
            
            $desc = nl2br($object->description);
            $asset = $object->asset->name;
            
            TTransaction::close();

            $window = TWindow::create("Detalhes do Chamado #{$param['id']}", 0.6, null);
            $window->add(new TLabel("<b>Equipamento:</b> {$asset}"));
            $window->add(new TLabel("<br><br><b>Relato do Problema:</b><br><div style='background:#f9f9f9; padding:10px; border:1px solid #ddd'>{$desc}</div>"));
            $window->show();
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onReload($param = NULL)
    {
        try {
            TTransaction::open(self::$database);
            $repository = new TRepository(self::$activeRecord);
            $criteria = $this->filter_criteria ? clone $this->filter_criteria : new TCriteria;
            
            $criteria->setProperties($param);
            $objects = $repository->load($criteria, FALSE);
            $this->datagrid->clear();
            if ($objects) { foreach ($objects as $object) $this->datagrid->addItem($object); }
            $count = $repository->count($criteria);
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit(10);
            TTransaction::close();
            $this->loaded = true;
        } catch (Exception $e) { new TMessage('error', $e->getMessage()); TTransaction::rollback(); }
    }

    public function onSearch()
    {
        $data = $this->form->getData();
        $this->filter_criteria = new TCriteria;
        if ($data->title) $this->filter_criteria->add(new TFilter('title', 'like', "%{$data->title}%"));
        $this->form->setData($data);
        $this->onReload();
    }

    public function show() { if (!$this->loaded) $this->onReload(); parent::show(); }
}