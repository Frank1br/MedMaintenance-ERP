<?php
class MaintenanceOrder extends TRecord
{
    const TABLENAME  = 'maintenance_orders';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial';

    private $asset;
    private $technician;

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('asset_id');
        parent::addAttribute('technician_id');
        parent::addAttribute('title');
        parent::addAttribute('description');
        parent::addAttribute('solution_notes');
        parent::addAttribute('priority');
        parent::addAttribute('status');
        parent::addAttribute('opened_at');
        parent::addAttribute('closed_at');
        parent::addAttribute('system_version');
    }

    /**
     * Relacionamento: Pertence a um Equipamento (Asset)
     * Permite usar: $os->asset->name na listagem
     */
    public function get_asset()
    {
        if (empty($this->asset))
            $this->asset = new Asset($this->asset_id);
        return $this->asset;
    }

    /**
     * Relacionamento: Pertence a um Técnico (Technician)
     * Permite usar: $os->technician->name na listagem
     */
    public function get_technician()
    {
        // --- ATUALIZAÇÃO AQUI ---
        // Se o ID do técnico estiver vazio (ainda não foi atribuído), 
        // retornamos um objeto genérico para a Datagrid não dar erro e mostrar uma mensagem bonita.
        if (empty($this->technician_id)) {
            return (object) ['name' => '<span style="color:gray; font-style:italic">Não atribuído</span>'];
        }

        // Carregamento normal se tiver ID
        if (empty($this->technician))
            $this->technician = new Technician($this->technician_id);
            
        return $this->technician;
    }
}
?>