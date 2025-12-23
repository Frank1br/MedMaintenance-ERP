<?php
class Asset extends TRecord
{
    const TABLENAME  = 'assets';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial';

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
        parent::addAttribute('serial_number');
        parent::addAttribute('patrimony_code');
        parent::addAttribute('manufacturer');
        parent::addAttribute('model');
        parent::addAttribute('status');
        parent::addAttribute('technical_specs'); // Campo JSONB
        parent::addAttribute('purchase_date');
        parent::addAttribute('warranty_expires_at');
        parent::addAttribute('location_sector');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
        parent::addAttribute('system_version');
    }

    /**
     * Helper para lidar com o JSONB de forma transparente
     * Exemplo: $asset->getSpec('voltagem');
     */
    public function getSpec($key)
    {
        $specs = json_decode($this->technical_specs ?? '{}', true);
        return $specs[$key] ?? null;
    }

    /**
     * Helper para salvar dado no JSONB
     * Exemplo: $asset->setSpec('voltagem', '220v');
     */
    public function setSpec($key, $value)
    {
        $specs = json_decode($this->technical_specs ?? '{}', true);
        $specs[$key] = $value;
        $this->technical_specs = json_encode($specs);
    }
}
?>