<?php
/**
 * MaintenanceOrderForm
 * Corrigido: Erro de BreadCrumb (Menu) e setPlaceholder removido
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
        $status->addItems(['ABERTA' => 'Aberta', 'EM ANDAMENTO' => 'Em Andamento', 'FECHADA' => 'Fechada']);
        
        $description = new TText('description');
        
        // --- NOVO CAMPO: SOLUÇÃO ---
        $solution = new TText('solution');
        $solution->setSize('100%', 100);
        // ---------------------------

        $id->setEditable(false);
        $asset_id->enableSearch();
        $technician_id->enableSearch();

        $id->setSize('20%');
        $asset_id->setSize('100%');
        $technician_id->setSize('100%');
        $priority->setSize('100%');
        $status->setSize('100%');
        $description->setSize('100%', 80);

        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Equipamento')], [$asset_id] );
        $this->form->addFields( [new TLabel('Técnico')], [$technician_id] );
        $this->form->addFields( [new TLabel('Prioridade')], [$priority], [new TLabel('Status')], [$status] );
        $this->form->addFields( [new TLabel('Descrição do Problema')], [$description] );
        
        // Adiciona o novo campo ao layout com destaque
        $this->form->addFields( [new TLabel('Relatório Técnico / Solução Final:', '#007bff')] );
        $this->form->addFields( [$solution] );

        $asset_id->addValidation('Equipamento', new TRequiredValidator);
        $priority->addValidation('Prioridade', new TRequiredValidator);
        $status->addValidation('Status', new TRequiredValidator);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addAction('Voltar', new TAction(['MaintenanceOrderList', 'onReload']), 'fa:arrow-left gray');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        
        // --- CORREÇÃO AQUI ---
        // Apontamos para a LISTA (que existe no menu) em vez do formulário
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
            
            if (empty($object->title)) {
                $asset = new Asset($data->asset_id);
                $object->title = 'OS - ' . $asset->name; 
            }
            
            $object->store();
            $data->id = $object->id;
            $this->form->setData($data);
            TTransaction::close();
            new TMessage('info', 'Registro salvo com sucesso!');
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
        }
    }

    public function onClear($param)
    {
        $this->form->clear(true);
    }
}