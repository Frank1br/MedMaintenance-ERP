<?php
/**
 * MaintenanceOrderForm
 * Formulário de Cadastro de OS (Com função onClear)
 */
class MaintenanceOrderForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        $this->setTargetContainer('adianti_div_content');

        $this->form = new BootstrapFormBuilder('form_MaintenanceOrder');
        $this->form->setFormTitle('Cadastro de Ordem de Serviço');

        $id = new TEntry('id');
        $asset_id = new TDBCombo('asset_id', 'med_maintenance', 'Asset', 'id', 'name');
        $technician_id = new TDBCombo('technician_id', 'med_maintenance', 'Technician', 'id', 'name');
        
        $priority = new TCombo('priority');
        $priority->addItems(['BAIXA' => 'Baixa', 'MEDIA' => 'Média', 'ALTA' => 'Alta', 'URGENTE' => 'Urgente']);
        
        $status = new TCombo('status');
        $status->addItems(['ABERTA' => 'Aberta', 'PENDENTE' => 'Pendente', 'FECHADA' => 'Fechada']);
        
        // Ajuste de altura do campo de texto
        $description = new TText('description');
        $description->setSize('100%', 100); 

        $id->setEditable(FALSE);
        $id->setSize('20%');
        $asset_id->setSize('100%');
        $technician_id->setSize('100%');
        $priority->setSize('100%');
        $status->setSize('100%');

        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Equipamento')], [$asset_id] );
        $this->form->addFields( [new TLabel('Técnico')], [$technician_id] );
        $this->form->addFields( [new TLabel('Prioridade')], [$priority], [new TLabel('Status')], [$status] );
        $this->form->addFields( [new TLabel('Descrição do Problema')], [$description] );

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addAction('Voltar', new TAction(['MaintenanceOrderList', 'onReload']), 'fa:arrow-left');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'MaintenanceOrderList'));
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
            $object->store();
            $data->id = $object->id;
            $this->form->setData($data);
            TTransaction::close();
            new TMessage('info', 'Registro salvo com sucesso');
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

    // ✅ A FUNÇÃO QUE FALTAVA
    public function onClear($param)
    {
        $this->form->clear(true);
    }
}