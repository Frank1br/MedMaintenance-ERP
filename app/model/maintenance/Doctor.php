<?php
class Doctor extends TRecord
{
    const TABLENAME = 'doctor';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // Serial ou Max

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
        parent::addAttribute('crm');
        parent::addAttribute('specialty');
        parent::addAttribute('email');
        parent::addAttribute('phone');
        parent::addAttribute('system_user_id');
    }
}