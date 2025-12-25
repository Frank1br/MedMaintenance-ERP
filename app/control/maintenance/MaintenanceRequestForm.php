<?php
/**
 * MaintenanceRequestForm
 * Portal do Solicitante: Formulário simplificado (Versão Final Corrigida)
 */
class MaintenanceRequestForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        $this->setTargetContainer('adianti_div_content');

        // Cria o formulário
        $this->form = new BootstrapFormBuilder('form_MaintenanceRequest');
        $this->form->setFormTitle('Solicitar Manutenção (Chamado)');
        $this->form->setClientValidation(true);

        // --- CAMPOS SIMPLIFICADOS ---
        
        // 1. Qual equipamento?
        $asset_id = new TDBCombo('asset_id', 'med_maintenance', 'Asset', 'id', 'name');
        $asset_id->enableSearch(); 
        
        // 2. Qual a urgência?
        $priority = new TCombo('priority');
        $priority->addItems([
            'BAIXA' => 'Baixa (Pode esperar)',
            'MEDIA' => 'Média (Atrapalha o serviço)',
            'ALTA' => 'Alta (Urgente)',
            'URGENTE' => 'Crítica (Risco ao paciente)'
        ]);
        $priority->setValue('MEDIA'); 
        
        // 3. O que aconteceu?
        $description = new TText('description');
        $description->setSize('100%', 100);

        // Layout Responsivo
        $this->form->addFields( [new TLabel('Equipamento:', 'red')], [$asset_id] );
        $this->form->addFields( [new TLabel('Prioridade:')], [$priority] );
        $this->form->addFields( [new TLabel('Descrição do Problema:', 'red')], [$description] );

        // Validações
        $asset_id->addValidation('Equipamento', new TRequiredValidator);
        $description->addValidation('Descrição', new TRequiredValidator);

        // --- AÇÃO DO BOTÃO ---
        $btn = $this->form->addAction('Abrir Chamado', new TAction([$this, 'onSave']), 'fa:bullhorn white');
        $btn->style = 'background-color: #e74c3c; color: white'; 

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

            $object = new MaintenanceOrder;
            $object->fromArray( (array) $data );
            
            // --- AUTOMAGIA: PREENCHIMENTO AUTOMÁTICO ---
            
            // 1. Define Status Inicial e Técnico vazio
            $object->status = 'ABERTA';       
            $object->technician_id = null;    
            
            // 2. Gera um Título Automático (Correção do erro NOT NULL)
            // Busca o nome do equipamento para usar no título
            $asset = new Asset($data->asset_id);
            $object->title = 'Chamado: ' . $asset->name; 
            
            // ---------------------------------------------

            $object->store();
            
            $this->form->clear(true);
            
            TTransaction::close();
            
            new TMessage('info', 'Chamado aberto com sucesso! Aguarde o atendimento.');
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}