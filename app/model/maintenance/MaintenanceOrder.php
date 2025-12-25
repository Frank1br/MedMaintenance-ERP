<?php
class MaintenanceOrder extends TRecord
{
    const TABLENAME  = 'maintenance_orders';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // Mantido conforme seu código atual

    private $asset;
    private $technician;

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('asset_id');
        parent::addAttribute('technician_id');
        parent::addAttribute('title');
        parent::addAttribute('description');
        parent::addAttribute('solution_notes'); // Campo antigo (se existir no form)
        parent::addAttribute('priority');
        parent::addAttribute('status');
        parent::addAttribute('opened_at');
        parent::addAttribute('closed_at');
        parent::addAttribute('system_version');
        
        // --- NOVO CAMPO ADICIONADO ---
        parent::addAttribute('solution'); 
    }

    /**
     * Relacionamento: Pertence a um Equipamento (Asset)
     */
    public function get_asset()
    {
        if (empty($this->asset))
            $this->asset = new Asset($this->asset_id);
        return $this->asset;
    }

    /**
     * Relacionamento: Pertence a um Técnico (Technician)
     */
    public function get_technician()
    {
        // Se o ID do técnico estiver vazio, retorna objeto genérico para evitar erro
        if (empty($this->technician_id)) {
            return (object) ['name' => '<span style="color:gray; font-style:italic">Não atribuído</span>'];
        }

        if (empty($this->technician))
            $this->technician = new Technician($this->technician_id);
            
        return $this->technician;
    }
}
?>