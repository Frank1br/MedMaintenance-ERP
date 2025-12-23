<?php
/**
 * MaintenanceOrderList
 * Lista de Ordens de Serviço
 * @author Tech Lead (Gemini)
 */
class MaintenanceOrderList extends TPage
{
    protected $form;     
    protected $datagrid; 
    protected $pageNavigation;
    
    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('med_maintenance');
        $this->setActiveRecord('MaintenanceOrder');
        $this->setDefaultOrder('id', 'desc');
        
        $this->addFilterField('id', '=', 'id');
        $this->addFilterField('title', 'like', 'title');

        // --- 1. Formulário de Busca ---
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

        $col_id    = new TDataGridColumn('id', 'Nº OS', 'center', '10%');
        $col_asset = new TDataGridColumn('{asset->name}', 'Equipamento', 'left', '25%');
        $col_tech  = new TDataGridColumn('{technician->name}', 'Técnico Resp.', 'left', '20%');
        $col_title = new TDataGridColumn('title', 'Problema', 'left', '25%');
        $col_prior = new TDataGridColumn('priority', 'Prioridade', 'center', '10%');
        $col_status= new TDataGridColumn('status', 'Status', 'center', '10%');

        // --- TRANSFORMADORES VISUAIS (CORES FORÇADAS PARA LEITURA PERFEITA) ---

        // Prioridade
        $col_prior->setTransformer(function($value) {
            $colors = [
                'BAIXA'   => '#28a745', // Verde
                'ALTA'    => '#dc3545', // Vermelho
                'URGENTE' => '#bd2130', // Vermelho Escuro
            ];
            
            // Cor padrão (Cinza)
            $color = '#6c757d'; 

            // Tratamento especial para MEDIA (Laranja)
            if ($value == 'MEDIA') $color = '#e67e22';
            else if (isset($colors[$value])) $color = $colors[$value];
            
            return "<span class='badge' style='background-color: {$color}; color: white; display:block; width: 100%'>$value</span>";
        });

        // Status (CORRIGIDO AQUI)
        $col_status->setTransformer(function($value) {
            $colors = [
                'ABERTA'    => '#007bff', // Azul Forte
                'FECHADA'   => '#28a745', // Verde Forte (AGORA VAI FICAR LEGÍVEL)
                'CANCELADA' => '#dc3545', // Vermelho Forte
            ];
            
            $color = $colors[$value] ?? '#6c757d'; // Cinza se não achar
            
            return "<span class='badge' style='background-color: {$color}; color: white; display:block; width: 100%'>$value</span>";
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_asset);
        $this->datagrid->addColumn($col_tech);
        $this->datagrid->addColumn($col_title);
        $this->datagrid->addColumn($col_prior);
        $this->datagrid->addColumn($col_status);

        // --- 3. Ações ---
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