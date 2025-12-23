<?php
/**
 * TechnicianList
 * Lista de Técnicos
 * @author Tech Lead (Gemini)
 */
class TechnicianList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    
    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('med_maintenance');
        $this->setActiveRecord('Technician');
        $this->setDefaultOrder('name', 'asc');
        $this->addFilterField('name', 'like', 'name'); // Filtro pelo nome

        // --- Busca ---
        $this->form = new BootstrapFormBuilder('form_search_Technician');
        $this->form->setFormTitle('Técnicos Cadastrados');
        
        $name = new TEntry('name');
        $name->setProperty('placeholder', 'Buscar técnico...');
        
        $this->form->addFields( [new TLabel('Nome')], [$name] )->layout = ['col-sm-2', 'col-sm-10'];
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addAction('Novo', new TAction(['TechnicianForm', 'onClear']), 'fa:plus green');

        // --- Grid ---
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_id    = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_name  = new TDataGridColumn('name', 'Nome', 'left', '40%');
        $col_spec  = new TDataGridColumn('specialty', 'Especialidade', 'left', '30%');
        $col_active= new TDataGridColumn('active', 'Ativo', 'center', '20%');

        // Transformador para mostrar "Sim/Não" colorido
        $col_active->setTransformer(function($value) {
            if ($value == 'Y') return "<span class='badge badge-success'>Sim</span>";
            return "<span class='badge badge-danger'>Não</span>";
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_name);
        $this->datagrid->addColumn($col_spec);
        $this->datagrid->addColumn($col_active);

        // Ações
        $action_edit = new TDataGridAction(['TechnicianForm', 'onEdit']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fa:edit blue');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);

        $action_del = new TDataGridAction([$this, 'onDelete']);
        $action_del->setLabel('Excluir');
        $action_del->setImage('fa:trash red');
        $action_del->setField('id');
        $this->datagrid->addAction($action_del);

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        $vbox->add($this->datagrid);
        $vbox->add($this->pageNavigation);

        parent::add($vbox);
    }
}
?>