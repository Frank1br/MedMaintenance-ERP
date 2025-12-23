<?php
/**
 * MaintenanceOrderForm
 * @author Tech Lead (Gemini)
 */
class MaintenanceOrderForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_MaintenanceOrder');
        $this->form->setFormTitle('Abertura de Ordem de Serviço (OS)');
        $this->form->setClientValidation(true);

        // --- Campos ---
        $id = new TEntry('id');
        $id->setEditable(false);
        
        // Combo que busca os Equipamentos do Banco
        $asset_id = new TDBCombo('asset_id', 'med_maintenance', 'Asset', 'id', 'name');
        $asset_id->enableSearch(); 
        
        $priority = new TCombo('priority');
        $priority->addItems([
            'BAIXA' => '🟢 Baixa',
            'MEDIA' => '🟡 Média',
            'ALTA'  => '🔴 Alta',
            'URGENTE' => '🔥 URGENTE'
        ]);
        $priority->setValue('MEDIA');

        $title = new TEntry('title');
        $description = new TText('description');
        $description->setSize('100%', 100);
        // Correção do Placeholder que fizemos antes
        $description->setProperty('placeholder', 'Descreva o defeito detalhadamente...');

        // Validações
        $asset_id->addValidation('Equipamento', new TRequiredValidator);
        $title->addValidation('Título do Problema', new TRequiredValidator);
        $description->addValidation('Descrição', new TRequiredValidator);

        // --- Layout ---
        $this->form->addFields([new TLabel('Nº OS')], [$id])->layout = ['col-sm-2', 'col-sm-10'];
        
        $this->form->addFields(
            [new TLabel('Equipamento Alvo*', '#ff0000'), $asset_id],
            [new TLabel('Prioridade'), $priority]
        )->layout = ['col-sm-8', 'col-sm-4'];
        
        $this->form->addFields([new TLabel('Título do Problema*', '#ff0000'), $title]);
        $this->form->addFields([new TLabel('Descrição Detalhada*', '#ff0000'), $description]);

        // --- Botões ---
        $btn = $this->form->addAction('Salvar Chamado', new TAction([$this, 'onSave']), 'fa:save white');
        $btn->addStyleClass('btn-primary');
        
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

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
            $data = $this->form->getData();

            // Validação do Service Layer (Bloqueio de Sucata)
            EquipmentService::validateMaintenanceRequest($data->asset_id);

            $object = new MaintenanceOrder();
            $object->fromArray( (array) $data);
            
            // Define status inicial apenas se for inclusão
            if (empty($object->id)) {
                $object->status = 'ABERTA';
                $object->opened_at = date('Y-m-d H:i:s');
            }
            
            $object->store();

            $data->id = $object->id;
            $this->form->setData($data);
            
            TTransaction::close();
            
            new TMessage('info', 'Chamado salvo com sucesso! OS: ' . $object->id);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * ESTE É O MÉTODO QUE FALTAVA!
     * Carrega os dados do banco para o formulário quando clicamos em Editar
     */
    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                $key = $param['key']; // Pega o ID da OS vindo da lista
                TTransaction::open('med_maintenance'); 
                $object = new MaintenanceOrder($key); // Carrega o objeto
                $this->form->setData($object); // Joga na tela
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