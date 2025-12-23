<?php
/**
 * MaintenanceOrderForm
 * Cadastro de Ordens de Serviço (Com Trava de Segurança para Técnicos)
 */
class MaintenanceOrderForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_MaintenanceOrder');
        $this->form->setFormTitle('Cadastro de Ordem de Serviço');

        $id = new TEntry('id');
        $asset_id = new TDBCombo('asset_id', 'med_maintenance', 'Asset', 'id', 'name');
        
        // Campo Técnico (TDBCombo)
        $technician_id = new TDBCombo('technician_id', 'med_maintenance', 'Technician', 'id', 'name');
        
        $title = new TEntry('title');
        $description = new TText('description');
        $priority = new TCombo('priority');
        $status = new TCombo('status');
        $created_at = new TDateTime('created_at');

        // Opções manuais
        $priority->addItems(['BAIXA' => 'Baixa', 'MEDIA' => 'Média', 'ALTA' => 'Alta', 'URGENTE' => 'Urgente']);
        $status->addItems(['ABERTA' => 'Aberta', 'EM ANDAMENTO' => 'Em Andamento', 'FECHADA' => 'Fechada']);

        // Configurações visuais
        $id->setEditable(FALSE);
        $created_at->setEditable(FALSE);
        $created_at->setMask('dd/mm/yyyy hh:ii');
        $created_at->setDatabaseMask('yyyy-mm-dd hh:ii');
        
        // Define data/hora atual se for novo cadastro
        if (empty($created_at->getValue())) {
            $created_at->setValue(date('Y-m-d H:i'));
        }

        $id->setSize('20%');
        $asset_id->setSize('100%');
        $technician_id->setSize('100%');
        $asset_id->enableSearch();
        $technician_id->enableSearch();

        // --- 🔒 LÓGICA DE SEGURANÇA DO TÉCNICO ---
        // 1. Verifica se o usuário logado é ADMIN
        $is_admin = false;
        $user_id = TSession::getValue('userid');
        TTransaction::open('permission');
        $user_groups = SystemUserGroup::where('system_user_id', '=', $user_id)->load();
        foreach ($user_groups as $group) {
            if ($group->system_group_id == 1) $is_admin = true;
        }
        TTransaction::close();

        // 2. Se NÃO for Admin, força o técnico logado
        if (!$is_admin) {
            TTransaction::open('med_maintenance');
            $logged_tech = Technician::where('system_user_id', '=', $user_id)->first();
            TTransaction::close();

            if ($logged_tech) {
                // Define o valor do campo como o ID do técnico logado
                $technician_id->setValue($logged_tech->id);
                // Bloqueia o campo para ele não mudar
                $technician_id->setEditable(FALSE);
            }
        }
        // ------------------------------------------

        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Equipamento')], [$asset_id] );
        $this->form->addFields( [new TLabel('Técnico Responsável')], [$technician_id] );
        $this->form->addFields( [new TLabel('Título Curto')], [$title] );
        $this->form->addFields( [new TLabel('Descrição do Problema')], [$description] );
        
        $row = $this->form->addFields( 
            [new TLabel('Prioridade'), $priority], 
            [new TLabel('Status Atual'), $status],
            [new TLabel('Data Abertura'), $created_at]
        );
        $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Voltar', new TAction(['MaintenanceOrderList', 'onReload']), 'fa:arrow-left');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'MaintenanceOrderList'));
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
            $object = new MaintenanceOrder;
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
                $object = new MaintenanceOrder($key);
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
}