<?php
/**
 * DoctorList
 * Listagem de Médicos
 */
class DoctorList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $loaded;
    
    public function __construct()
    {
        parent::__construct();
        $this->setTargetContainer('adianti_div_content');

        // 1. Formulário de Busca
        $this->form = new BootstrapFormBuilder('form_search_Doctor');
        $this->form->setFormTitle('Médicos Cadastrados');

        $name = new TEntry('name');
        
        $this->form->addFields( [new TLabel('Nome do Médico:')], [$name] );
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Novo Médico', new TAction(['DoctorForm', 'onClear']), 'fa:plus green');

        // 2. Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        // Colunas
        $col_id = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_name = new TDataGridColumn('name', 'Nome', 'left', '40%');
        $col_crm = new TDataGridColumn('crm', 'CRM', 'center', '15%');
        $col_spec = new TDataGridColumn('specialty', 'Especialidade', 'left', '20%');
        $col_phone = new TDataGridColumn('phone', 'Telefone', 'center', '15%');

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_name);
        $this->datagrid->addColumn($col_crm);
        $this->datagrid->addColumn($col_spec);
        $this->datagrid->addColumn($col_phone);

        // Ações
        $action_edit = new TDataGridAction(['DoctorForm', 'onEdit']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fa:edit blue');
        $action_edit->setField('id');

        $action_del = new TDataGridAction([$this, 'onDelete']);
        $action_del->setLabel('Excluir');
        $action_del->setImage('fa:trash red');
        $action_del->setField('id');

        $this->datagrid->addAction($action_edit);
        $this->datagrid->addAction($action_del);

        $this->datagrid->createModel();

        // 3. Navegação
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

    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('med_maintenance');
            $repository = new TRepository('Doctor');
            $criteria = new TCriteria;
            
            // Filtro de busca
            $data = $this->form->getData();
            if ($data->name) {
                $criteria->add(new TFilter('name', 'like', "%{$data->name}%"));
            }

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
        $this->onReload();
    }

    public function onDelete($param)
    {
        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);
        new TQuestion('Deseja excluir?', $action);
    }

    public function Delete($param)
    {
        try {
            TTransaction::open('med_maintenance');
            $object = new Doctor($param['id']);
            $object->delete();
            TTransaction::close();
            $this->onReload();
            new TMessage('info', 'Registro excluído');
        } catch (Exception $e) { new TMessage('error', $e->getMessage()); }
    }

    public function show() { if (!$this->loaded) $this->onReload(); parent::show(); }
}