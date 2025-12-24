<?php
/**
 * Technician Active Record
 */
class Technician extends TRecord
{
    const TABLENAME = 'technicians';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial'; // {max, serial}
    
    // Adicione esta trait se estiver usando SystemUser para log de criação
    // use SystemChangeLogTrait; 

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        
        // Definição dos atributos que podem ser salvos
        parent::addAttribute('name');
        parent::addAttribute('email');
        parent::addAttribute('phone');
        parent::addAttribute('specialty'); // Vi essa coluna no seu print do banco
        parent::addAttribute('active');
        parent::addAttribute('system_user_id');
        parent::addAttribute('signature'); 
    }

    /**
     * Relacionamento com SystemUser (Opcional, mas bom ter)
     */
    public function get_system_user()
    {
        if (empty($this->system_user))
        {
            $this->system_user = new SystemUser($this->system_user_id);
        }
        return $this->system_user;
    }
}