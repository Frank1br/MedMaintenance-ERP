<?php
/**
 * MaintenanceOrderList
 * Lista de Ordens de Serviço
 * @author Tech Lead (Gemini)
 */
class MaintenanceOrderList extends TPage
{
    protected $form;     // Formulário de busca
    protected $datagrid; // Listagem
    protected $pageNavigation;
    
    // Trait que adiciona métodos padrões de listagem (onReload, onDelete, etc)
    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('med_maintenance'); // Banco de dados
        $this->setActiveRecord('MaintenanceOrder'); // Tabela principal
        $this->setDefaultOrder('id', 'desc'); // Ordem padrão (mais recentes primeiro)
        $this->addFilterField('id', '=', 'id'); // Campo de busca
        $this->addFilterField('title', 'like', 'title'); // Campo de busca

        // --- 1. Formulário de Busca Rápida ---
        $this->form = new BootstrapFormBuilder('form_search_MaintenanceOrder');
        $this->form->setFormTitle('Gestão de Ordens de Serviço');
        
        $title = new TEntry('title');
        $title->setProperty('placeholder', 'Buscar por título...');
        
        $this->form->addFields( [new TLabel('Busca')], [$title] )->layout = ['col-sm-2', 'col-sm-10'];
        
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addAction('Nova OS', new TAction(['MaintenanceOrderForm', 'onClear']), 'fa:plus green');

        // --- 2. A Datagrid (Tabela) ---
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        // Colunas
        $col_id    = new TDataGridColumn('id', 'Nº OS', 'center', '10%');
        
        // Aqui usamos a mágica do ORM: {asset->name} chama o método get_asset() e pega o name
        $col_asset = new TDataGridColumn('{asset->name}', 'Equipamento', 'left', '30%');
        
        $col_title = new TDataGridColumn('title', 'Problema', 'left', '30%');
        $col_prior = new TDataGridColumn('priority', 'Prioridade', 'center', '15%');
        $col_status= new TDataGridColumn('status', 'Status', 'center', '15%');

        // Transformadores (Deixar bonito)
        $col_prior->setTransformer(function($value) {
            $class = ($value == 'URGENTE') ? 'danger' : 'info';
            return "<span class='badge badge-{$class}'>$value</span>";
        });

        $col_status->setTransformer(function($value) {
            $colors = ['ABERTA'=>'primary', 'FECHADA'=>'success', 'CANCELADA'=>'secondary'];
            $color = $colors[$value] ?? 'light';
            return "<span class='badge badge-{$color}'>$value</span>";
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_asset);
        $this->datagrid->addColumn($col_title);
        $this->datagrid->addColumn($col_prior);
        $this->datagrid->addColumn($col_status);

        // --- 3. Ações da Linha (Editar/Excluir) ---
        $action_edit = new TDataGridAction(['MaintenanceOrderForm', 'onEdit']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fa:edit blue');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);

        $action_del = new TDataGridAction([$this, 'onDelete']);
        $action_del->setLabel('Excluir');
        $action_del->setImage('fa:trash red');
        $action_del->setField('id');
        $this->datagrid->addAction($action_del);

        // Criar o modelo da grid
        $this->datagrid->createModel();

        // Navegação de página
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        // Empacotamento
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        $vbox->add($this->datagrid);
        $vbox->add($this->pageNavigation);

        parent::add($vbox);
    }
}
?>