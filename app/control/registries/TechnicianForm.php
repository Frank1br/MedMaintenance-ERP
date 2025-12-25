<?php
/**
 * TechnicianForm
 * Cadastro de Técnicos (Versão Robusta: Força Bruta no Salvamento)
 */
class TechnicianForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_Technician');
        $this->form->setFormTitle('Cadastro de Técnico');

        $id = new TEntry('id');
        $name = new TEntry('name');
        $specialty = new TEntry('specialty');
        $email = new TEntry('email');
        $phone = new TEntry('phone');
        
        $active = new TRadioGroup('active');
        $active->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $active->setLayout('horizontal');
        $active->setValue('Y'); 

        $system_user_id = new TDBCombo('system_user_id', 'permission', 'SystemUser', 'id', 'name');
        $system_user_id->enableSearch(); 
        
        // Componente de Assinatura
        if (class_exists('TSignaturePad')) {
            $signature = new TSignaturePad('signature');
            $signature->setLabel('Assinatura Digital');
            $signature->setSize('100%', 200); 
            $signature->setDrawSize(800, 400); 
            $signature->setPenStyle('#000000', 2);
        } else {
            // Fallback (plano B)
            $signature = new TFile('signature');
            $signature->setLabel('Assinatura (Arquivo)');
        }

        $id->setEditable(FALSE);
        $id->setSize('20%');
        $name->setSize('100%');
        $specialty->setSize('100%');
        $email->setSize('100%');
        $system_user_id->setSize('100%');

        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Nome Completo')], [$name] );
        $this->form->addFields( [new TLabel('Especialidade')], [$specialty] );
        $this->form->addFields( [new TLabel('Email')], [$email] );
        $this->form->addFields( [new TLabel('Telefone')], [$phone] );
        $this->form->addFields( [new TLabel('Login de Acesso')], [$system_user_id] );
        $this->form->addFields( [new TLabel('Ativo?')], [$active] );
        $this->form->addFields( [new TLabel('Assinatura')], [$signature] );

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addAction('Voltar', new TAction(['TechnicianList', 'onReload']), 'fa:arrow-left');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'TechnicianList'));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onSave()
    {
        try
        {
            TTransaction::open('med_maintenance'); 
            $this->form->validate(); 
            $data = $this->form->getData(); 
            
            
            $final_signature_name = null;
            $raw_value = $data->signature;

            if (is_array($raw_value)) {
                $final_signature_name = $raw_value[0] ?? null;
            }

            elseif (is_string($raw_value) && strpos($raw_value, '[') !== false) {
                $decoded = json_decode($raw_value);
                if (is_array($decoded)) {
                    $final_signature_name = $decoded[0] ?? null;
                } else {
                    $final_signature_name = $raw_value; 
                }
            }

            elseif (is_string($raw_value) && !empty($raw_value)) {
                $final_signature_name = $raw_value;
            }


            if ($final_signature_name) {
                $target_folder = 'files/signatures';
                $target_path   = $target_folder . '/' . $final_signature_name;
                $source_path   = 'tmp/' . $final_signature_name; 
                
                if (!file_exists($target_folder)) mkdir($target_folder, 0777, true);
                
                if (file_exists($source_path)) rename($source_path, $target_path);
            }

            // Salvar no Banco
            $object = new Technician; 
            $object->fromArray( (array) $data); 
            

            if ($final_signature_name) {
                $object->signature = $final_signature_name;
            }
            
            $object->store(); 
            $this->form->setData($object); 
            TTransaction::close(); 
            

            $msg = $final_signature_name ? "Arquivo salvo: $final_signature_name" : "Atenção: Assinatura vazia!";
            new TMessage('info', 'Registro Salvo! ' . $msg);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key']; 
                TTransaction::open('med_maintenance');
                $object = new Technician($key); 
                $this->form->setData($object); 
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onClear($param)
    {
        $this->form->clear(true);
    }
}