<?php
/**
 * DoctorForm
 * Cadastro de Médicos
 */
class DoctorForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        $this->setTargetContainer('adianti_div_content');

        $this->form = new BootstrapFormBuilder('form_Doctor');
        $this->form->setFormTitle('Cadastro de Médico');

        $id = new TEntry('id');
        $name = new TEntry('name');
        $crm = new TEntry('crm');
        $specialty = new TEntry('specialty');
        $email = new TEntry('email');
        $phone = new TEntry('phone');

        $id->setEditable(false);
        $id->setSize('30%');
        $name->setSize('100%');
        $crm->setSize('100%');
        $specialty->setSize('100%');
        $email->setSize('100%');
        $phone->setSize('100%');

        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Nome Completo', 'red')], [$name] );
        $this->form->addFields( [new TLabel('CRM')], [$crm], [new TLabel('Especialidade')], [$specialty] );
        $this->form->addFields( [new TLabel('E-mail')], [$email], [new TLabel('Telefone')], [$phone] );

        $name->addValidation('Nome', new TRequiredValidator);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Novo', new TAction([$this, 'onClear']), 'fa:plus blue');
        $this->form->addAction('Voltar', new TAction(['DoctorList', 'onReload']), 'fa:arrow-left gray');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        parent::add($vbox);
    }

    public function onSave()
    {
        try {
            TTransaction::open('med_maintenance');
            $this->form->validate();
            $data = $this->form->getData();
            $object = new Doctor;
            $object->fromArray( (array) $data );
            $object->store();
            $data->id = $object->id;
            $this->form->setData($data);
            TTransaction::close();
            new TMessage('info', 'Médico salvo com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                $key = $param['key'];
                TTransaction::open('med_maintenance');
                $object = new Doctor($key);
                $this->form->setData($object);
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClear($param)
    {
        $this->form->clear(true);
    }
}