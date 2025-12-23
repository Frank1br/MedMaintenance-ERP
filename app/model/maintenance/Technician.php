<?php
class Technician extends TRecord
{
    const TABLENAME  = 'technicians';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // PostgreSQL usa Serial/Sequence

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
        parent::addAttribute('email');
        parent::addAttribute('phone');
        parent::addAttribute('specialty');
        parent::addAttribute('active');
        parent::addAttribute('system_version');
    }
}
?>