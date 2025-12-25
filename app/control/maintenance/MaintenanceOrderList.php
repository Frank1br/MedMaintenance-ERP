<?php
/**
 * MaintenanceOrderList
 * Versão Completa: Portal do Solicitante (Visualização) + Resend API + Dompdf
 */
class MaintenanceOrderList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'med_maintenance';
    private static $activeRecord = 'MaintenanceOrder';
    private static $primaryKey = 'id';
    private static $formName = 'formList_MaintenanceOrder';

    public function __construct()
    {
        parent::__construct();
        $this->setTargetContainer('adianti_div_content');

        // 1. FORMULÁRIO DE BUSCA
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('Ordens de Serviço (Gestão)');

        $title = new TEntry('title');
        $status = new TCombo('status');
        $status->addItems(['ABERTA' => 'Aberta', 'EM ANDAMENTO' => 'Em Andamento', 'FECHADA' => 'Fechada']);

        $this->form->addFields( [new TLabel('Título')], [$title] );
        $this->form->addFields( [new TLabel('Status')], [$status] );

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        // Botão para criar nova OS manualmente (Visão do Técnico/Admin)
        $this->form->addAction('Nova OS Interna', new TAction(['MaintenanceOrderForm', 'onClear']), 'fa:plus green');

        // 2. DATAGRID
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        $this->datagrid->datatable = 'false';

        // 3. AÇÕES INDIVIDUAIS (PREPARAÇÃO)
        $action_edit = new TDataGridAction(['MaintenanceOrderForm', 'onEdit']);
        $action_edit->setLabel('Editar / Atribuir Técnico');
        $action_edit->setImage('fa:edit blue');
        $action_edit->setField(self::$primaryKey);

        $action_pdf = new TDataGridAction(['MaintenanceOrderDocument', 'onGenerate']);
        $action_pdf->setLabel('Imprimir OS');
        $action_pdf->setImage('fa:print gray');
        $action_pdf->setField(self::$primaryKey);

        $action_email = new TDataGridAction([$this, 'onSendEmail']);
        $action_email->setLabel('Enviar por E-mail');
        $action_email->setImage('fa:envelope blue');
        $action_email->setField(self::$primaryKey);

        $action_del = new TDataGridAction([$this, 'onDelete']);
        $action_del->setLabel('Excluir Registro');
        $action_del->setImage('fa:trash red');
        $action_del->setField(self::$primaryKey);

        // 4. MENU DROP-DOWN (OPÇÕES)
        $action_group = new TDataGridActionGroup('Opções', 'fa:th');
        $action_group->addHeader('Gerenciar OS');
        $action_group->addAction($action_edit);
        $action_group->addAction($action_pdf);
        $action_group->addAction($action_email);
        $action_group->addSeparator();
        $action_group->addAction($action_del);

        $this->datagrid->addActionGroup($action_group);

        // 5. COLUNAS (COM A NOVA LÓGICA DE TRIAGEM)
        $col_id = new TDataGridColumn('id', 'ID', 'center', '5%');
        $col_asset = new TDataGridColumn('{asset->name}', 'Equipamento', 'left', '25%');
        
        // --- MUDANÇA AQUI: Coluna Técnico com Transformer ---
        $col_tech = new TDataGridColumn('technician_id', 'Técnico', 'left', '25%');
        
        $col_tech->setTransformer(function($value, $object, $row) {
            // Se existir um ID de técnico e o objeto técnico for carregado
            if (!empty($value) && $object->technician) {
                return $object->technician->name;
            } else {
                // Se estiver vazio (vinda do Portal do Solicitante)
                return "<span class='label label-warning' style='font-size:11px; padding: 4px;'><i class='fa fa-exclamation-triangle'></i> A TRIAR</span>";
            }
        });
        // ----------------------------------------------------

        $col_status = new TDataGridColumn('status', 'Status', 'center', '15%');
        $col_priority = new TDataGridColumn('priority', 'Prioridade', 'center', '15%');

        $col_status->setTransformer(function($value) {
            $class = ($value == 'FECHADA') ? 'success' : (($value == 'ABERTA') ? 'danger' : 'warning');
            return "<span class='label label-{$class}'>{$value}</span>";
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_asset);
        $this->datagrid->addColumn($col_tech);
        $this->datagrid->addColumn($col_status);
        $this->datagrid->addColumn($col_priority);

        $this->datagrid->createModel();

        // 6. NAVEGAÇÃO
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add($this->datagrid);
        $vbox->add($this->pageNavigation);

        parent::add($vbox);
    }

    /**
     * Passo 1: Janela de Confirmação (TWindow)
     */
    public function onSendEmail($param)
    {
        try {
            $key = $param['key'] ?? $param['id']; 
            
            TTransaction::open(self::$database);
            $order = new MaintenanceOrder($key);
            
            // Lógica para pegar email: se tem técnico, pega dele. Se não, deixa vazio.
            $suggested_email = '';
            if ($order->technician_id) {
                $technician = new Technician($order->technician_id);
                $suggested_email = $technician->email; 
            }
            TTransaction::close();

            $form = new BootstrapFormBuilder('form_email_popup');
            
            $email_dest = new TEntry('email_dest');
            $email_dest->setValue($suggested_email); 
            $email_dest->setSize('100%');
            
            // Dica visual sobre o plano grátis
            $lbl_info = new TLabel('Nota: No plano grátis Resend, envie apenas para seu e-mail de cadastro.');
            $lbl_info->setFontColor('red');
            $lbl_info->setFontSize(10);
            
            $form->addFields([new TLabel('E-mail de Destino:')], [$email_dest]);
            $form->addFields([], [$lbl_info]);
            
            $action = new TAction([$this, 'sendEmailNow']);
            $action->setParameter('id', $key);
            $form->addAction('Enviar Agora', $action, 'fa:paper-plane green');
            
            $window = TWindow::create('Enviar OS via Resend API', 0.6, null);
            $window->add($form);
            $window->show();
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Passo 2: Disparo via RESEND API (Com Dompdf e cURL)
     */
    public function sendEmailNow($param)
    {
        try {
            $key = $param['id'];
            $dest_email = $param['email_dest'];
            
            $env_path = getcwd() . '/.env';
            $env = file_exists($env_path) ? parse_ini_file($env_path) : [];

            if (empty($env['RESEND_API_KEY'])) throw new Exception("Configure a RESEND_API_KEY no .env");

            TTransaction::open(self::$database);
            $object = new MaintenanceOrder($key);
            $asset = new Asset($object->asset_id);
            
            // Verifica se tem técnico para exibir no PDF
            $tech_name = ($object->technician) ? $object->technician->name : 'Aguardando Atribuição';

            // --- GERAÇÃO DO PDF (DOMPDF) ---
            $pdf_path = "tmp/OS_{$key}.pdf";
            
            $html = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 12px; }
                    h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
                    .label { font-weight: bold; color: #555; }
                    .box { background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; }
                    .alert { color: red; font-weight: bold; }
                </style>
            </head>
            <body>
                <h1>Ordem de Serviço #{$key}</h1>
                <p><b>Data:</b> " . date('d/m/Y H:i') . "</p>
                
                <div class='box'>
                    <p><span class='label'>Equipamento:</span> {$asset->name}</p>
                    <p><span class='label'>Técnico Responsável:</span> {$tech_name}</p>
                    <p><span class='label'>Status Atual:</span> {$object->status}</p>
                    <p><span class='label'>Prioridade:</span> {$object->priority}</p>
                </div>
                
                <h3>Descrição do Problema</h3>
                <div class='box'>
                    " . nl2br($object->description) . "
                </div>
                
                <br><br>
                <hr>
                <center><small>Documento gerado automaticamente pelo MedMaintenance ERP</small></center>
            </body>
            </html>";

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            file_put_contents($pdf_path, $dompdf->output());
            
            if (!file_exists($pdf_path) || filesize($pdf_path) < 100) {
                throw new Exception("Falha ao gerar PDF.");
            }
            TTransaction::close();

            $pdf_content = file_get_contents($pdf_path);
            $pdf_base64 = base64_encode($pdf_content);

            // --- ENVIO VIA API (cURL) ---
            $url = "https://api.resend.com/emails";
            
            $data = [
                "from" => "onboarding@resend.dev",
                "to" => [$dest_email],
                "subject" => "Ordem de Serviço #{$key} - MedMaintenance",
                "html" => "<p>Olá,</p><p>Segue em anexo a <strong>OS #{$key}</strong>.</p><p>Att,<br>MedMaintenance ERP</p>",
                "attachments" => [
                     [
                         "filename" => "OS_{$key}.pdf",
                         "content" => $pdf_base64
                     ]
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $env['RESEND_API_KEY'],
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) { throw new Exception('Erro cURL: ' . curl_error($ch)); }
            curl_close($ch);

            if ($http_code >= 200 && $http_code < 300) {
                // Limpeza Automática do arquivo temporário
                if (file_exists($pdf_path)) { unlink($pdf_path); }
                
                TWindow::closeWindow();
                new TMessage('info', "Sucesso! Enviado via Resend para: <b>$dest_email</b>");
            } else {
                $error_data = json_decode($response, true);
                $msg_erro = $error_data['message'] ?? $response;
                if (strpos($msg_erro, 'domain') !== false) {
                    $msg_erro .= "<br><br><b>Dica:</b> No plano grátis, o remetente DEVE ser 'onboarding@resend.dev'.";
                }
                throw new Exception("Erro API Resend ({$http_code}): " . $msg_erro);
            }
            
        } catch (Exception $e) {
            new TMessage('error', 'Falha no envio: ' . $e->getMessage());
        }
    }

    private function applySystemSecurity($criteria)
    {
        $logged_user_id = TSession::getValue('userid');
        TTransaction::open('permission');
        $is_admin = false;
        $user_groups = SystemUserGroup::where('system_user_id', '=', $logged_user_id)->load();
        foreach ($user_groups as $group) { if ($group->system_group_id == 1) $is_admin = true; }
        TTransaction::close();

        if (!$is_admin) {
            TTransaction::open(self::$database);
            $technician = Technician::where('system_user_id', '=', $logged_user_id)->first();
            TTransaction::close();
            if ($technician) $criteria->add(new TFilter('technician_id', '=', $technician->id));
            else $criteria->add(new TFilter('id', '<', 0));
        }
    }

    public function onReload($param = NULL)
    {
        try {
            TTransaction::open(self::$database);
            $repository = new TRepository(self::$activeRecord);
            $criteria = $this->filter_criteria ? clone $this->filter_criteria : new TCriteria;
            $this->applySystemSecurity($criteria);
            $limit = 10;
            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);
            $objects = $repository->load($criteria, FALSE);
            $this->datagrid->clear();
            if ($objects) { foreach ($objects as $object) $this->datagrid->addItem($object); }
            $count = $repository->count($criteria);
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);
            TTransaction::close();
            $this->loaded = true;
        } catch (Exception $e) { new TMessage('error', $e->getMessage()); TTransaction::rollback(); }
    }

    public function onSearch()
    {
        $data = $this->form->getData();
        $this->filter_criteria = new TCriteria;
        if ($data->title) $this->filter_criteria->add(new TFilter('title', 'like', "%{$data->title}%"));
        if ($data->status) $this->filter_criteria->add(new TFilter('status', '=', $data->status));
        $this->form->setData($data);
        $this->onReload();
    }

    public function onDelete($param)
    {
        $action = new TAction([$this, 'Delete']);
        $action->setParameters($param);
        new TQuestion('Deseja realmente excluir?', $action);
    }

    public function Delete($param)
    {
        try {
            TTransaction::open(self::$database);
            $object = new MaintenanceOrder($param['id']);
            $object->delete();
            TTransaction::close();
            $this->onReload();
            new TMessage('info', 'Registro excluído');
        } catch (Exception $e) { new TMessage('error', $e->getMessage()); }
    }

    public function show() { if (!$this->loaded) $this->onReload(); parent::show(); }
}