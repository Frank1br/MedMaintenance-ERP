<?php
/**
 * TechnicianForm
 * Cadastro de Técnicos com TSignaturePad
 */
class TechnicianForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        // Cria o formulário
        $this->form = new BootstrapFormBuilder('form_Technician');
        $this->form->setFormTitle('Cadastro de Técnico');

        // Criação dos campos básicos
        $id = new TEntry('id');
        $name = new TEntry('name');
        $email = new TEntry('email');
        $phone = new TEntry('phone');
        
        $active = new TRadioGroup('active');
        $active->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $active->setLayout('horizontal');
        $active->setValue('Y'); 

        $system_user_id = new TDBCombo('system_user_id', 'permission', 'SystemUser', 'id', 'name');
        $system_user_id->enableSearch(); 
        
        // --- ✍️ AJUSTE PARA O SEU COMPONENTE (TSignaturePad) ---
        // Baseado no seu print de referência
        try {
            $signature = new TSignaturePad('signature'); 
            $signature->setLabel('Assinatura Digital');
            
            // Configurações do seu print
            $signature->setSize('100%', 200); // Tamanho na tela
            $signature->setDrawSize(800, 400); // Resolução do desenho
            $signature->setPenStyle('#000000', 2); // Cor preta, espessura 2
            
            $signature->setTip('Assine acima usando o mouse');
        }
        catch (Error $e) {
            // Fallback apenas se der erro grave
            $signature = new TLabel('Erro ao carregar componente TSignaturePad.');
            $signature->setFontColor('red');
        }
        // -------------------------------------------------------

        // Propriedades
        $id->setEditable(FALSE);
        $id->setSize('20%');
        $name->setSize('100%');
        $email->setSize('100%');
        $system_user_id->setSize('100%');

        // Layout
        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Nome Completo')], [$name] );
        $this->form->addFields( [new TLabel('Email')], [$email] );
        $this->form->addFields( [new TLabel('Telefone')], [$phone] );
        $this->form->addFields( [new TLabel('Login de Acesso')], [$system_user_id] );
        $this->form->addFields( [new TLabel('Ativo?')], [$active] );

        // Adiciona a Assinatura
        $this->form->addFields( [new TLabel('Assinatura')], [$signature] );

        // Botões
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
            
            // --- LÓGICA DE SALVAMENTO ---
            // O TSignaturePad geralmente salva a imagem em tmp/ e retorna o nome
            // Vamos mover para a pasta definitiva files/signatures
            if (!empty($data->signature))
            {
                $target_folder = 'files/signatures';
                $target_path   = $target_folder . '/' . $data->signature;
                $source_path   = 'tmp/' . $data->signature;
                
                if (!file_exists($target_folder)) {
                    mkdir($target_folder, 0777, true);
                }
                
                // Verifica se é um arquivo (comportamento padrão)
                if (file_exists($source_path)) {
                    rename($source_path, $target_path);
                }
            }

            $object = new Technician; 
            $object->fromArray( (array) $data); 
            $object->store(); 
            $this->form->setData($object); 
            TTransaction::close(); 
            
            new TMessage('info', 'Registro salvo com sucesso');
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