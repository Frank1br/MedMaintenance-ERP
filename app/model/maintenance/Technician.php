<?php
class Technician extends TRecord
{
    const TABLENAME  = 'technicians'; // Confirme se está no plural aqui também
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; 

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
        parent::addAttribute('email');
        parent::addAttribute('phone');
        parent::addAttribute('active');
        // ✅ O NOVO CAMPO:
        parent::addAttribute('system_user_id'); 
    }
    
    /**
     * Método para buscar o Nome do Usuário vinculado (para listas e relatórios)
     */
    public function get_system_user()
    {
        // Busca na conexão 'permission' (onde ficam os usuários padrão do Adianti)
        return SystemUser::find($this->system_user_id);
    }
}
?>