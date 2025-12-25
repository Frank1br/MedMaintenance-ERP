<?php
/**
 * MaintenanceOrderDocument
 * Versão Final: Assinaturas Alinhadas pelo Topo
 */
class MaintenanceOrderDocument extends TPage
{
    public function __construct()
    {
        parent::__construct();
    }

    public function onGenerate($param)
    {
        try
        {
            // Validação de ID
            if (!isset($param['key'])) {
                throw new Exception('ID da Ordem de Serviço não informado.');
            }
            $key = $param['key'];

            TTransaction::open('med_maintenance');

            $object = new MaintenanceOrder($key);
            $asset_name = $object->asset->name;
            $tech = $object->technician;
            $tech_name = $tech ? $tech->name : 'Não atribuído';

            // ============================================================
            // 1. PROCESSAMENTO DA ASSINATURA (CAMINHOS ABSOLUTOS)
            // ============================================================
            $signature_html = ''; 
            $root_path = getcwd();

            if ($tech && !empty($tech->signature)) {
                
                $full_path_png = $root_path . "/files/signatures/{$tech->signature}";
                
                if (file_exists($full_path_png)) {
                    $source_img = @imagecreatefrompng($full_path_png);

                    if ($source_img) {
                        $width = imagesx($source_img);
                        $height = imagesy($source_img);

                        $white_bg_img = imagecreatetruecolor($width, $height);
                        $white = imagecolorallocate($white_bg_img, 255, 255, 255);
                        imagefill($white_bg_img, 0, 0, $white);

                        imagecopy($white_bg_img, $source_img, 0, 0, 0, 0, $width, $height);

                        $temp_jpg_name = "sig_fixed_{$key}.jpg";
                        $temp_jpg_path = $root_path . "/tmp/" . $temp_jpg_name;
                        
                        imagejpeg($white_bg_img, $temp_jpg_path, 100);
                        imagedestroy($source_img);
                        imagedestroy($white_bg_img);

                        if (file_exists($temp_jpg_path)) {
                            $signature_html = "<img src='{$temp_jpg_path}' style='width: 150px; max-height: 80px;'>";
                        }
                    } else {
                        $signature_html = "<img src='{$full_path_png}' style='width: 150px; max-height: 80px;'>";
                    }
                }
            }

            // ============================================================
            // 2. PROCESSAMENTO DO RELATÓRIO TÉCNICO
            // ============================================================
            $solution_text = !empty($object->solution) 
                ? nl2br($object->solution) 
                : "<i>Nenhuma solução registrada pelo técnico.</i>";

            $date_open = !empty($object->opened_at) ? TDate::date2br($object->opened_at) : date('d/m/Y');

            // ============================================================
            // 3. GERAÇÃO DO HTML (CSS Ajustado)
            // ============================================================
            $html = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; }
                    .header { width: 100%; border-bottom: 2px solid #007bff; margin-bottom: 20px; padding-bottom: 10px; }
                    .title { float: right; font-weight: bold; color: #555; }
                    .brand { float: left; font-weight: bold; font-size: 14pt; }
                    
                    .section-title { 
                        background-color: #f0f0f0; 
                        border-left: 5px solid #007bff; 
                        padding: 5px 10px; 
                        margin-top: 20px; 
                        margin-bottom: 10px; 
                        font-weight: bold; 
                        text-transform: uppercase; 
                        font-size: 10pt;
                    }
                    
                    .data-row { margin-bottom: 5px; }
                    .label { font-weight: bold; width: 150px; display: inline-block; }
                    
                    .box-text { 
                        border: 1px solid #ddd; padding: 10px; background: #fdfdfd; min-height: 60px; text-align: justify;
                    }

                    .box-solution { 
                        border: 1px solid #b8daff; padding: 10px; background: #e8f4fd; min-height: 60px; text-align: justify; color: #004085;
                    }

                    /* --- AJUSTE DE ALINHAMENTO --- */
                    .signatures { margin-top: 60px; width: 100%; text-align: center; }
                    
                    /* vertical-align: top garante que os blocos comecem na mesma altura */
                    .sign-block { display: inline-block; width: 45%; vertical-align: top; }
                    
                    .sign-line { border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 5px; font-size: 9pt; }
                    
                    /* Altura fixa para a área da imagem garante que a linha comece no mesmo ponto */
                    .sign-img-container { height: 90px; margin-bottom: 5px; text-align: center; display: block; }
                    
                    .footer { margin-top: 50px; font-size: 8pt; text-align: center; color: #999; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <span class='brand'>MedMaintenance ERP</span>
                    <span class='title'>Ordem de Serviço #{$object->id}</span>
                    <div style='clear:both'></div>
                </div>

                <div class='section-title'>Dados Gerais</div>
                <div class='data-row'><span class='label'>Equipamento:</span> {$asset_name}</div>
                <div class='data-row'><span class='label'>Técnico Responsável:</span> {$tech_name}</div>
                <div class='data-row'><span class='label'>Data de Abertura:</span> {$date_open}</div>
                <div class='data-row'><span class='label'>Status:</span> <b>{$object->status}</b> (Prioridade: {$object->priority})</div>

                <div class='section-title'>Descrição do Problema</div>
                <div class='box-text'>
                    " . nl2br($object->description) . "
                </div>

                <div class='section-title' style='border-left-color: #28a745;'>Relatório Técnico / Solução</div>
                <div class='box-solution'>
                    {$solution_text}
                </div>

                <div class='signatures'>
                    <div class='sign-block'>
                        <div class='sign-img-container'>
                            {$signature_html}
                        </div>
                        <div class='sign-line'>
                            Assinatura do Técnico<br>
                            <b>{$tech_name}</b>
                        </div>
                    </div>

                    <div class='sign-block'>
                        <div class='sign-img-container'>
                             </div>
                        <div class='sign-line'>
                            Assinatura do Responsável (Setor)
                        </div>
                    </div>
                </div>

                <div class='footer'>
                    Documento gerado automaticamente em " . date('d/m/Y H:i') . "
                </div>
            </body>
            </html>";

            // ============================================================
            // 4. RENDERIZAÇÃO
            // ============================================================
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('chroot', $root_path);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $file = "tmp/os_{$object->id}.pdf";
            file_put_contents($file, $dompdf->output());

            $window = TWindow::create('Ordem de Serviço', 0.8, 0.8);
            $object = new TElement('object');
            $object->data  = $file;
            $object->type  = 'application/pdf';
            $object->style = "width: 100%; height:calc(100% - 10px)";
            $window->add($object);
            $window->show();

            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}