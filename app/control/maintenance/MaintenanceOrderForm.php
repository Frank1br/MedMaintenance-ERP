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
        // Parâmetros: (nome_campo, banco, model, id, coluna_para_mostrar)
        $asset_id = new TDBCombo('asset_id', 'med_maintenance', 'Asset', 'id', 'name');
        $asset_id->enableSearch(); // Permite digitar para buscar
        
        // Combo de Prioridade
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

        // --- Botão de Salvar (Com Validação de Serviço) ---
        $btn = $this->form->addAction('Abrir Chamado', new TAction([$this, 'onSave']), 'fa:save white');
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

            // --- AQUI ESTÁ A MÁGICA (CHAMADA AO SERVICE LAYER) ---
            // Antes de salvar, perguntamos ao Service se pode.
            // Se não puder, ele lança um Exception e o código pula para o catch block.
            EquipmentService::validateMaintenanceRequest($data->asset_id);

            // Se passou, salva normal
            $object = new MaintenanceOrder();
            $object->fromArray( (array) $data);
            $object->status = 'ABERTA';
            $object->opened_at = date('Y-m-d H:i:s');
            $object->store();

            $data->id = $object->id;
            $this->form->setData($data);
            
            TTransaction::close();
            
            new TMessage('info', 'Chamado aberto com sucesso! OS: ' . $object->id);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage()); // Exibe o erro do Service (ex: "Equipamento Baixado")
            TTransaction::rollback();
        }
    }

    public function onClear($param)
    {
        $this->form->clear(true);
    }
}
?>