<?php
/**
 * TechnicianForm
 *
 * Cadastro de Técnicos
 */
class TechnicianForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        // Cria o formulário usando Bootstrap
        $this->form = new BootstrapFormBuilder('form_Technician');
        $this->form->setFormTitle('Cadastro de Técnico');

        // Criação dos campos
        $id = new TEntry('id');
        $name = new TEntry('name');
        $email = new TEntry('email');
        $phone = new TEntry('phone');
        
        // Campo Radio para Ativo/Inativo
        $active = new TRadioGroup('active');
        $active->addItems(['Y' => 'Sim', 'N' => 'Não']);
        $active->setLayout('horizontal');
        $active->setValue('Y'); // Padrão é Sim

        // Campo de Vínculo com Usuário do Sistema
        // Parâmetros: nome_campo, banco_busca, model_busca, campo_chave, campo_exibicao
        $system_user_id = new TDBCombo('system_user_id', 'permission', 'SystemUser', 'id', 'name');
        $system_user_id->enableSearch(); // Permite digitar para pesquisar na lista
        $system_user_id->setTip('Selecione qual usuário de login corresponde a este técnico');

        // Propriedades dos campos
        $id->setEditable(FALSE);
        $id->setSize('20%');
        $name->setSize('100%');
        $email->setSize('100%');
        $system_user_id->setSize('100%');

        // Adiciona os campos ao formulário (Layout)
        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Nome Completo')], [$name] );
        $this->form->addFields( [new TLabel('Email')], [$email] );
        $this->form->addFields( [new TLabel('Telefone')], [$phone] );
        
        // Adiciona o novo campo de Login
        $this->form->addFields( [new TLabel('Login de Acesso (Vínculo)')], [$system_user_id] );
        
        $this->form->addFields( [new TLabel('Ativo?')], [$active] );

        // Ações do formulário (Botões)
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addAction('Voltar', new TAction(['TechnicianList', 'onReload']), 'fa:arrow-left');

        // Container visual
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'TechnicianList'));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    /**
     * Salva os dados
     */
    public function onSave()
    {
        try
        {
            TTransaction::open('med_maintenance'); // Abre a transação
            
            $this->form->validate(); // Valida o formulário
            
            $data = $this->form->getData(); // Pega os dados do form
            
            $object = new Technician; 
            $object->fromArray( (array) $data); // Preenche o objeto
            
            $object->store(); // Salva no banco
            
            $this->form->setData($object); // Mantém os dados na tela
            
            TTransaction::close(); // Fecha transação
            
            new TMessage('info', 'Registro salvo com sucesso');
        }
        catch (Exception $e) // Em caso de erro
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Carrega os dados para editar
     */
    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // Obtém a chave
                TTransaction::open('med_maintenance');
                $object = new Technician($key); // Carrega o objeto
                $this->form->setData($object); // Joga os dados no formulário
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Limpa o formulário
     */
    public function onClear($param)
    {
        $this->form->clear(true);
    }
}