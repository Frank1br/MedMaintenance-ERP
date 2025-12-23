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
        $this->form->setFormTitle('Ordem de Serviço (OS)');
        $this->form->setClientValidation(true);

        // --- Campos ---
        $id = new TEntry('id');
        $id->setEditable(false);
        
        // 1. Combo de Equipamentos
        $asset_id = new TDBCombo('asset_id', 'med_maintenance', 'Asset', 'id', 'name');
        $asset_id->enableSearch(); 
        
        // 2. Combo de Técnicos (Apenas ativos)
        $filter_tech = new TCriteria;
        $filter_tech->add(new TFilter('active', '=', 'Y'));
        
        $technician_id = new TDBCombo('technician_id', 'med_maintenance', 'Technician', 'id', 'name', 'name', $filter_tech);
        $technician_id->enableSearch();
        $technician_id->setProperty('placeholder', 'Selecione um técnico...');

        // 3. Prioridade e Status
        $priority = new TCombo('priority');
        $priority->addItems([
            'BAIXA' => '🟢 Baixa',
            'MEDIA' => '🟡 Média',
            'ALTA'  => '🔴 Alta',
            'URGENTE' => '🔥 URGENTE'
        ]);
        $priority->setValue('MEDIA');

        // Status (Leitura apenas - controlado pelos botões)
        $status = new TEntry('status');
        $status->setEditable(false);
        $status->setValue('ABERTA'); // Valor padrão visual

        $title = new TEntry('title');
        
        // Descrição do Problema
        $description = new TText('description');
        $description->setSize('100%', 80);
        $description->setProperty('placeholder', 'Descreva o defeito detalhadamente...');

        // --- NOVIDADE: Notas de Solução ---
        $solution_notes = new TText('solution_notes');
        $solution_notes->setSize('100%', 80);
        $solution_notes->setProperty('placeholder', 'Obrigatório para finalizar: O que foi feito para resolver?');
        $solution_notes->style = "background-color: #f9fff9; border-color: #28a745"; // Destaque visual leve

        // --- Validações ---
        $asset_id->addValidation('Equipamento', new TRequiredValidator);
        $title->addValidation('Título do Problema', new TRequiredValidator);
        $description->addValidation('Descrição', new TRequiredValidator);

        // --- Layout ---
        $this->form->addFields([new TLabel('Nº OS')], [$id])->layout = ['col-sm-2', 'col-sm-10'];
        
        $this->form->addFields(
            [new TLabel('Equipamento Alvo*', '#ff0000'), $asset_id],
            [new TLabel('Status Atual'), $status]
        )->layout = ['col-sm-8', 'col-sm-4'];

        $this->form->addFields(
            [new TLabel('Técnico Responsável'), $technician_id],
            [new TLabel('Prioridade'), $priority]
        )->layout = ['col-sm-8', 'col-sm-4'];
        
        $this->form->addFields([new TLabel('Título do Problema*', '#ff0000'), $title]);
        $this->form->addFields([new TLabel('Descrição do Defeito*', '#ff0000'), $description]);
        
        // Adiciona um divisor visual
        $this->form->addContent( ['<h5 style="color:#28a745; margin-top:20px"><i class="fa fa-check-circle"></i> Encerramento Técnico</h5>'] );
        
        $this->form->addFields([new TLabel('Solução Aplicada (Para finalizar)', '#28a745')], [$solution_notes]);

        // --- Botões ---
        
        // 1. Salvar (Apenas atualiza dados)
        $btn_save = $this->form->addAction('Salvar Alterações', new TAction([$this, 'onSave']), 'fa:save white');
        $btn_save->addStyleClass('btn-primary');
        
        // 2. Finalizar (Encerra a OS)
        $btn_finish = $this->form->addAction('FINALIZAR CHAMADO', new TAction([$this, 'onFinish']), 'fa:check white');
        $btn_finish->addStyleClass('btn-success'); // Botão Verde
        
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

            // Validação de Duplicidade (Passando o ID atual para ignorar a própria OS)
            EquipmentService::validateMaintenanceRequest($data->asset_id, $data->id);

            $object = new MaintenanceOrder();
            $object->fromArray( (array) $data);
            
            if (empty($object->id)) {
                $object->status = 'ABERTA';
                $object->opened_at = date('Y-m-d H:i:s');
            }
            
            $object->store();

            $data->id = $object->id;
            $this->form->setData($data);
            
            TTransaction::close();
            
            new TMessage('info', 'Dados atualizados com sucesso!');
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Lógica de Finalização da OS
     */
    public function onFinish($param = null)
    {
        try {
            TTransaction::open('med_maintenance');
            
            // Pega os dados da tela
            $data = $this->form->getData();
            
            // 1. Validação Específica para Fechamento
            if (empty($data->solution_notes)) {
                throw new Exception("<b>ATENÇÃO:</b> Para finalizar o chamado, você deve preencher o campo 'Solução Aplicada' explicando o que foi feito.");
            }
            
            if (empty($data->technician_id)) {
                 throw new Exception("<b>ATENÇÃO:</b> É necessário atribuir um Técnico Responsável antes de finalizar.");
            }

            // 2. Carrega e Atualiza o Objeto
            $object = new MaintenanceOrder();
            $object->fromArray( (array) $data);
            
            // Aplica o fechamento
            $object->status = 'FECHADA';
            $object->closed_at = date('Y-m-d H:i:s');
            
            $object->store();
            
            // Atualiza a tela
            $data->id = $object->id;
            $data->status = 'FECHADA';
            $this->form->setData($data);
            
            TTransaction::close();
            
            new TMessage('info', '<b>PARABÉNS!</b><br>Ordem de Serviço finalizada com sucesso.');
            
        } catch (Exception $e) {
            new TMessage('warning', $e->getMessage()); // Warning usa amarelo/laranja
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