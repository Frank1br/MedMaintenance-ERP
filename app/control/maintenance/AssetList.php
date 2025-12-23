<?php
/**
 * AssetList
 * Lista de Equipamentos
 */
class AssetList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    
    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('med_maintenance');
        $this->setActiveRecord('Asset');
        $this->setDefaultOrder('id', 'asc');
        $this->addFilterField('name', 'like', 'name');

        // --- Formulário de Busca ---
        $this->form = new BootstrapFormBuilder('form_search_Asset');
        $this->form->setFormTitle('Invent&aacute;rio de Equipamentos'); // Acento Corrigido
        
        $name = new TEntry('name');
        $name->setProperty('placeholder', 'Nome ou Modelo...');
        
        $this->form->addFields( [new TLabel('Buscar')], [$name] )->layout = ['col-sm-2', 'col-sm-10'];
        
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addAction('Novo', new TAction(['AssetForm', 'onClear']), 'fa:plus green');

        // --- Grid ---
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_id     = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_name   = new TDataGridColumn('name', 'Nome do Equipamento', 'left', '40%');
        $col_serial = new TDataGridColumn('serial_number', 'N&ordm; S&eacute;rie', 'left', '20%'); // Acento Corrigido
        $col_status = new TDataGridColumn('status', 'Status', 'center', '20%');
        
        // --- CORREÇÃO DE CORES (MODO CLARO/ESCURO) ---
        $col_status->setTransformer(function($value) {
            $colors = [
                'OPERACIONAL' => '#28a745', // Verde
                'BAIXADO'     => '#dc3545', // Vermelho
                'MANUTENCAO'  => '#e67e22', // Laranja
            ];
            $color = $colors[$value] ?? '#6c757d';
            
            // Força cor da letra branca (color: white)
            return "<span class='badge' style='background-color: {$color}; color: white; display:block; width: 100%'>$value</span>";
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_name);
        $this->datagrid->addColumn($col_serial);
        $this->datagrid->addColumn($col_status);

        // --- Ações ---
        
        $action_edit = new TDataGridAction(['AssetForm', 'onEdit']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fa:edit blue');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);

        $action_del = new TDataGridAction([$this, 'onDelete']);
        $action_del->setLabel('Excluir');
        $action_del->setImage('fa:trash red');
        $action_del->setField('id');
        $this->datagrid->addAction($action_del);
        
        // Botão Histórico
        $action_hist = new TDataGridAction(['AssetHistory', 'onLoad']);
        $action_hist->setLabel('Ver Hist&oacute;rico'); // Acento Corrigido (HTML Entity)
        $action_hist->setImage('fa:history purple');
        $action_hist->setField('id');
        $this->datagrid->addAction($action_hist);

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