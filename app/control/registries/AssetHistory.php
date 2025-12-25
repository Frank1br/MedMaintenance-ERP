<?php
/**
 * AssetHistory
 * Linha do tempo de manutenções do equipamento
 */
class AssetHistory extends TPage
{
    public function __construct()
    {
        parent::__construct();
    }

    public function onLoad($param)
    {
        try {
            // Limpa a tela
            parent::clearChildren();
            
            $main_box = new TVBox;
            $main_box->style = 'width: 100%';

            // --- DEBUG: Verifica se o ID chegou ---
            $key = $param['key'] ?? $param['id'] ?? null;

            // Se não veio ID, avisa o usuário
            if (empty($key)) {
                $msg = new TMessage('info', '<b>Aguardando Seleção:</b><br>Por favor, vá para a Lista de Equipamentos e clique no botão Roxo (Ver Histórico) de um item.');
                $main_box->add($msg);
                parent::add($main_box);
                return;
            }

            // Tenta abrir conexão
            TTransaction::open('med_maintenance');
            
            // Carrega Equipamento
            $asset = new Asset($key);
            
            if (empty($asset->id)) {
                throw new Exception("Equipamento com ID {$key} não encontrado no banco.");
            }
            
            // --- Cabeçalho Visual ---
            $head = new TElement('div');
            $head->style = 'border-bottom: 1px solid #ccc; margin-bottom: 20px; padding-bottom: 10px;';
            
            // Título com HTML entities para evitar erro de acento
            $head->add(new TLabel("Hist&oacute;rico Cl&iacute;nico: <b>{$asset->name}</b>", '#333', '18px', 'B'));
            $head->add(new TElement('br'));
            
            $status_color = ($asset->status == 'OPERACIONAL') ? 'green' : 'red';
            $head->add(new TLabel("S&eacute;rie: {$asset->serial_number} | Status: <span class='badge' style='background-color:{$status_color}; color:white'>{$asset->status}</span>"));
            
            $main_box->add($head);

            // --- Busca as OS (Timeline) ---
            $orders = MaintenanceOrder::where('asset_id', '=', $key)
                                      ->orderBy('opened_at', 'desc')
                                      ->load();
            
            if ($orders) {
                $timeline = new TTimeline;
                
                foreach ($orders as $os) {
                    $icon = 'fa:wrench bg-blue';
                    if ($os->status == 'FECHADA')   $icon = 'fa:check bg-green';
                    if ($os->status == 'CANCELADA') $icon = 'fa:ban bg-red';
                    
                    // Formata Data
                    $date_fmt = 'Data N/D';
                    if (!empty($os->opened_at)) {
                        $date_obj = new DateTime($os->opened_at);
                        $date_fmt = $date_obj->format('d/m/Y H:i');
                    }
                    
                    $title = "OS #{$os->id} - {$os->priority}";
                    
                    // Corpo HTML
                    $html  = "<div style='line-height:1.5'>";
                    $html .= "<b>Problema:</b> {$os->title}<br>";
                    $html .= "<span style='color:#666'>{$os->description}</span><br>";
                    
                    if (!empty($os->solution_notes)) {
                        $html .= "<hr style='margin:5px 0'>";
                        $html .= "<b style='color:#28a745'>Solu&ccedil;&atilde;o:</b> {$os->solution_notes}<br>";
                    }
                    
                    // Nome do Técnico
                    if (!empty($os->technician_id)) {
                        // Tenta carregar objeto técnico com segurança
                        $tech_name = 'Técnico n/d';
                        try {
                            if ($os->technician) {
                                $tech_name = $os->technician->name;
                            }
                        } catch (Exception $e) {
                             $tech_name = 'Erro ao carregar nome';
                        }
                        $html .= "<br><small class='text-muted'><i class='fa fa-user-md'></i> {$tech_name}</small>";
                    }
                    $html .= "</div>";

                    $timeline->addItem($os->id, $title, $html, $date_fmt, $icon, 'left');
                }
                
                $main_box->add($timeline);
                
            } else {
                // Mensagem se não houver OS
                $alert = new TAlert('info', 'Este equipamento n&atilde;o possui manuten&ccedil;&otilde;es registradas.');
                $main_box->add($alert);
            }
            
            // Botão Voltar
            $btn_back = new TActionLink('Voltar para Invent&aacute;rio', new TAction(['AssetList', 'onReload']), 'white', null, null, 'fa:arrow-left');
            $btn_back->addStyleClass('btn btn-primary');
            $btn_back->style = 'margin-top: 20px';
            
            $main_box->add($btn_back);

            TTransaction::close();
            
            parent::add($main_box);
            
        } catch (Exception $e) {
            // Em caso de erro grave, mostra o erro na tela
            $err_box = new TVBox;
            $err_box->style = 'width: 100%; padding: 20px; background: #ffebeb; border: 1px solid red; color: red';
            $err_box->add(new TLabel("<b>ERRO CRÍTICO:</b> " . $e->getMessage()));
            parent::add($err_box);
            TTransaction::rollback();
        }
    }
}
?>