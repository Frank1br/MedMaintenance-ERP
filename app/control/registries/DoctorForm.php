<?php
/**
 * DoctorForm
 * Cadastro de Médicos (Com Assinatura e CRM)
 */
class DoctorForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        // Define o container principal
        $this->form = new BootstrapFormBuilder('form_Doctor');
        $this->form->setFormTitle('Cadastro de Médico');

        // --- DEFINIÇÃO DOS CAMPOS ---
        $id = new TEntry('id');
        $name = new TEntry('name');
        $crm = new TEntry('crm'); // Campo exclusivo de Médico
        $specialty = new TEntry('specialty');
        $email = new TEntry('email');
        $phone = new TEntry('phone');
        
        // Campo Ativo (Sim/Não)
        $active = new TRadioGroup('active');
        $active->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $active->setLayout('horizontal');
        $active->setValue('Y'); 

        // Vínculo com Usuário do Sistema (Login)
        $system_user_id = new TDBCombo('system_user_id', 'permission', 'SystemUser', 'id', 'name');
        $system_user_id->enableSearch(); 
        
        // Componente de Assinatura (Igual ao do Técnico)
        if (class_exists('TSignaturePad')) {
            $signature = new TSignaturePad('signature');
            $signature->setLabel('Assinatura Digital');
            $signature->setSize('100%', 200); 
            $signature->setDrawSize(800, 400); 
            $signature->setPenStyle('#000000', 2);
        } else {
            $signature = new TFile('signature');
            $signature->setLabel('Assinatura (Arquivo)');
        }

        // --- PROPRIEDADES ---
        $id->setEditable(FALSE);
        $id->setSize('20%');
        $name->setSize('100%');
        $crm->setSize('100%');
        $specialty->setSize('100%');
        $email->setSize('100%');
        $phone->setSize('100%');
        $system_user_id->setSize('100%');

        // --- ADICIONANDO CAMPOS AO FORMULÁRIO ---
        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Nome Completo')], [$name] );
        
        // Linha com CRM e Especialidade
        $this->form->addFields( [new TLabel('CRM')], [$crm], [new TLabel('Especialidade')], [$specialty] );
        
        $this->form->addFields( [new TLabel('Email')], [$email], [new TLabel('Telefone')], [$phone] );
        $this->form->addFields( [new TLabel('Login de Acesso (Vínculo)')], [$system_user_id] );
        $this->form->addFields( [new TLabel('Ativo?')], [$active] );
        
        // Campo de Assinatura
        $this->form->addFields( [new TLabel('Assinatura')], [$signature] );

        // --- AÇÕES DO FORMULÁRIO ---
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        // Botão voltar aponta para a LISTA (Corrige erro do menu)
        $this->form->addAction('Voltar', new TAction(['DoctorList', 'onReload']), 'fa:arrow-left');

        // --- EMPACOTAMENTO ---
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        // BreadCrumb aponta para a LISTA (Fundamental para evitar o erro do print)
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'DoctorList'));
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
            
            // --- LÓGICA ROBUSTA DE ASSINATURA ---
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

            // Move o arquivo da pasta temporária para a definitiva
            if ($final_signature_name) {
                $target_folder = 'files/signatures';
                $target_path   = $target_folder . '/' . $final_signature_name;
                $source_path   = 'tmp/' . $final_signature_name; 
                
                if (!file_exists($target_folder)) mkdir($target_folder, 0777, true);
                
                if (file_exists($source_path)) rename($source_path, $target_path);
            }

            // --- SALVAMENTO DO OBJETO ---
            $object = new Doctor; // Instancia a Model Doctor
            $object->fromArray( (array) $data); 
            
            if ($final_signature_name) {
                $object->signature = $final_signature_name;
            }
            
            $object->store(); 
            $this->form->setData($object); 
            TTransaction::close(); 
            
            $msg = $final_signature_name ? " (Assinatura salva)" : "";
            new TMessage('info', 'Médico salvo com sucesso!' . $msg);
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
                $object = new Doctor($key); 
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