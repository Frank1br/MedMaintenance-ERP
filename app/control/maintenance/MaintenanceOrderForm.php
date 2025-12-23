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
        
        // 1. Combo de Equipamentos
        $asset_id = new TDBCombo('asset_id', 'med_maintenance', 'Asset', 'id', 'name');
        $asset_id->enableSearch(); 
        
        // 2. Combo de Técnicos (NOVIDADE)
        // Criamos um filtro para só mostrar técnicos que estão Ativos (active = 'Y')
        $filter_tech = new TCriteria;
        $filter_tech->add(new TFilter('active', '=', 'Y'));
        
        $technician_id = new TDBCombo('technician_id', 'med_maintenance', 'Technician', 'id', 'name', 'name', $filter_tech);
        $technician_id->enableSearch();
        $technician_id->setProperty('placeholder', 'Selecione um técnico...');

        // 3. Prioridade
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

        // --- Validações ---
        $asset_id->addValidation('Equipamento', new TRequiredValidator);
        $title->addValidation('Título do Problema', new TRequiredValidator);
        $description->addValidation('Descrição', new TRequiredValidator);
        // Técnico não é obrigatório na abertura (pode ser atribuído depois), então sem validação por enquanto.

        // --- Layout ---
        $this->form->addFields([new TLabel('Nº OS')], [$id])->layout = ['col-sm-2', 'col-sm-10'];
        
        // Linha do Equipamento (Ocupa a linha toda agora para dar destaque)
        $this->form->addFields(
            [new TLabel('Equipamento Alvo*', '#ff0000'), $asset_id]
        );

        // Nova Linha: Técnico e Prioridade
        $this->form->addFields(
            [new TLabel('Técnico Responsável'), $technician_id],
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

            // Validação do Service Layer
            EquipmentService::validateMaintenanceRequest($data->asset_id);

            $object = new MaintenanceOrder();
            $object->fromArray( (array) $data); // (array) garantido aqui!
            
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

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                $key = $param['key'];
                TTransaction::open('med_maintenance'); 
                $object = new MaintenanceOrder($key);
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