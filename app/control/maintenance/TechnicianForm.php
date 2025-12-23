<?php
/**
 * TechnicianForm
 * Cadastro de Técnicos
 * @author Tech Lead (Gemini)
 */
class TechnicianForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_Technician');
        $this->form->setFormTitle('Cadastro de Técnico');

        // --- Campos ---
        $id = new TEntry('id');
        $name = new TEntry('name');
        $email = new TEntry('email');
        $phone = new TEntry('phone');
        $specialty = new TEntry('specialty');
        
        $active = new TRadioGroup('active');
        $active->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $active->setLayout('horizontal');
        $active->setValue('Y'); // Padrão Ativo

        // --- Validações ---
        $name->addValidation('Nome', new TRequiredValidator);
        $email->addValidation('Email', new TEmailValidator); 

        // --- Configurações Visuais ---
        $id->setEditable(false);
        $name->forceUpperCase();
        
        // --- Layout (AGORA CORRIGIDO) ---
        $this->form->addFields([new TLabel('ID')], [$id])->layout = ['col-sm-2', 'col-sm-10'];
        
        $this->form->addFields([new TLabel('Nome Completo*')], [$name]); // Ocupa a linha toda
        
        // CORREÇÃO AQUI: Note que Label e Input estão dentro do mesmo []
        $this->form->addFields(
            [new TLabel('Email'), $email],
            [new TLabel('Telefone'), $phone]
        )->layout = ['col-sm-6', 'col-sm-6']; // Divide meio a meio
        
        // CORREÇÃO AQUI TAMBÉM
        $this->form->addFields(
            [new TLabel('Especialidade'), $specialty],
            [new TLabel('Ativo?'), $active]
        )->layout = ['col-sm-8', 'col-sm-4'];

        // --- Botões ---
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save white')->addStyleClass('btn-primary');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addAction('Voltar', new TAction(['TechnicianList', 'onReload']), 'fa:arrow-left');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        parent::add($vbox);
    }

    public function onSave($param = null)
    {
        try {
            TTransaction::open('med_maintenance');
            $this->form->validate();
            
            $object = new Technician();
            
            // CORREÇÃO AQUI: Adicionado (array) antes de $this->form->getData()
            $object->fromArray( (array) $this->form->getData() );
            
            $object->store();
            
            $this->form->setData($object);
            TTransaction::close();
            
            new TMessage('info', 'Técnico salvo com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('med_maintenance');
                $object = new Technician($param['key']);
                $this->form->setData($object);
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onClear($param)
    {
        $this->form->clear(true);
    }
}
?>