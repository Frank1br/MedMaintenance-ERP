<?php
/**
 * AssetForm
 * * Tela de Cadastro de Equipamentos
 * @author Frank
 */
class AssetForm extends TPage
{
    protected $form; // Objeto do formulário

    public function __construct()
    {
        parent::__construct();

        // Cria o formulário visual usando Bootstrap (Responsivo)
        $this->form = new BootstrapFormBuilder('form_Asset');
        $this->form->setFormTitle('Cadastro de Equipamento Hospitalar');
        $this->form->setClientValidation(true); // Validação no navegador via JS

        // --- Definição dos Campos ---
        
        $id = new TEntry('id');
        $name = new TEntry('name');
        $serial_number = new TEntry('serial_number');
        $patrimony_code = new TEntry('patrimony_code');
        $manufacturer = new TEntry('manufacturer');
        $model = new TEntry('model');
        
        // Combo de Status (Vital para nossa regra de negócio)
        $status = new TCombo('status');
        $status->addItems([
            'OPERACIONAL' => '🟢 Operacional',
            'MANUTENCAO' => '🟠 Em Manutenção',
            'BAIXADO' => '🔴 Baixado (Sucata)',
            'EM_AQUISICAO' => '🔵 Em Aquisição'
        ]);
        $status->setValue('OPERACIONAL'); // Valor padrão
        
        $purchase_date = new TDate('purchase_date');
        $purchase_date->setMask('dd/mm/yyyy');
        $purchase_date->setDatabaseMask('yyyy-mm-dd');
        
        $warranty_expires_at = new TDate('warranty_expires_at');
        $warranty_expires_at->setMask('dd/mm/yyyy');
        $warranty_expires_at->setDatabaseMask('yyyy-mm-dd');
        
        $location_sector = new TEntry('location_sector');

        // Campo para o JSONB (Por enquanto um texto simples para não complicar)
        $technical_specs = new TText('technical_specs');
        $technical_specs->setProperty('placeholder', 'Ex: {"voltagem": "220v", "peso": "50kg"}');
        $technical_specs->setSize('100%', 70);

        // --- Configurações Visuais ---
        $id->setEditable(false);
        $id->setSize('100%');
        $name->setSize('100%');
        $name->forceUpperCase(); // Padronização
        $patrimony_code->setSize('100%');
        
        // Validações obrigatórias
        $name->addValidation('Nome do Equipamento', new TRequiredValidator);
        $patrimony_code->addValidation('Patrimônio', new TRequiredValidator);
        $status->addValidation('Status', new TRequiredValidator);

        // --- Montagem do Layout (Grid Bootstrap) ---
        
        // Linha 1: ID e Nome
        $this->form->addFields(
            [new TLabel('ID'), $id],
            [new TLabel('Nome do Equipamento*', '#ff0000'), $name]
        )->layout = ['col-sm-2', 'col-sm-10'];

        // Linha 2: Fabricante, Modelo, Nº Série
        $this->form->addFields(
            [new TLabel('Fabricante'), $manufacturer],
            [new TLabel('Modelo'), $model],
            [new TLabel('Nº Série'), $serial_number]
        )->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        // Linha 3: Patrimônio, Status, Setor
        $this->form->addFields(
            [new TLabel('Cód. Patrimônio*', '#ff0000'), $patrimony_code],
            [new TLabel('Status Atual*', '#ff0000'), $status],
            [new TLabel('Setor/Localização'), $location_sector]
        )->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        // Linha 4: Datas
        $this->form->addFields(
            [new TLabel('Data de Compra'), $purchase_date],
            [new TLabel('Garantia Até'), $warranty_expires_at]
        )->layout = ['col-sm-6', 'col-sm-6'];

        // Linha 5: Especificações Técnicas (JSON)
        $this->form->addFields(
            [new TLabel('Especificações Técnicas (JSON)'), $technical_specs]
        );

        // --- Botões de Ação ---
        $btn_save = $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save white');
        $btn_save->addStyleClass('btn-primary'); // Classe CSS Bootstrap
        
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        // $this->form->addAction('Voltar', new TAction(['WelcomeView', 'onReload']), 'fa:arrow-left');

        // Empacota tudo na página
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        // $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    /**
     * Salvar dados no Banco
     */
    public function onSave($param = null)
    {
        try {
            TTransaction::open('med_maintenance'); // Abre conexão
            
            $this->form->validate(); // Roda as validações dos campos
            
            $data = $this->form->getData(); // Pega dados da tela
            
            $object = new Asset(); // Instancia o Model
            $object->fromArray( (array) $data); // Preenche o Model
            
            $object->store(); // INSERT ou UPDATE automático
            
            // Preenche o ID gerado no campo da tela
            $data->id = $object->id;
            $this->form->setData($data);
            
            TTransaction::close(); // Fecha conexão e comita
            
            // Mensagem de Sucesso
            new TMessage('info', 'Equipamento salvo com sucesso! ID: ' . $object->id);
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onClear($param)
    {
        $this->form->clear(true);
    }
    
    /**
     * Carregar registro para edição (será usado pela Datagrid depois)
     */
    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                $key = $param['key'];
                TTransaction::open('med_maintenance');
                $object = new Asset($key);
                $this->form->setData($object);
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
?>