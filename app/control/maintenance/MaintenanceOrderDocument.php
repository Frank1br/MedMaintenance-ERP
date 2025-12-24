<?php
/**
 * MaintenanceOrderDocument
 * Gera PDF (Versão Final: Fundo Branco via Arquivo Temporário)
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
            $key = $param['id'];
            if (!$key) throw new Exception("ID não informado");

            TTransaction::open('med_maintenance');
            $object = new MaintenanceOrder($key);
            $asset = new Asset($object->asset_id);
            $technician = new Technician($object->technician_id);

            // 1. Tratamento da Imagem da Assinatura
            $signature_img = ''; 
            
            if (!empty($technician->signature)) {
                // Caminho absoluto do arquivo PNG original (Transparente/Preto)
                $full_path_png = getcwd() . '/files/signatures/' . $technician->signature;
                
                if (file_exists($full_path_png)) {
                    
                    // --- PROCESSAMENTO DA IMAGEM ---
                    
                    // a. Carrega o PNG original
                    $source_img = @imagecreatefrompng($full_path_png);

                    if ($source_img) {
                        $width = imagesx($source_img);
                        $height = imagesy($source_img);

                        // b. Cria uma folha branca
                        $white_bg_img = imagecreatetruecolor($width, $height);
                        $white = imagecolorallocate($white_bg_img, 255, 255, 255);
                        imagefill($white_bg_img, 0, 0, $white);

                        // c. Cola a assinatura em cima
                        imagecopy($white_bg_img, $source_img, 0, 0, 0, 0, $width, $height);

                        // d. SALVA UM ARQUIVO TEMPORÁRIO JPG (Fundo Branco Forçado)
                        // Usamos o ID da OS no nome para não misturar
                        $temp_jpg_name = "sig_fixed_{$key}.jpg";
                        $temp_jpg_path = getcwd() . "/tmp/" . $temp_jpg_name;
                        
                        imagejpeg($white_bg_img, $temp_jpg_path, 100);

                        // e. Limpa a memória
                        imagedestroy($source_img);
                        imagedestroy($white_bg_img);

                        // f. Aponta o HTML para esse arquivo temporário novo
                        if (file_exists($temp_jpg_path)) {
                            $signature_img = "<img src='{$temp_jpg_path}' style='width: 150px; max-height: 80px; display: block; margin: 0 auto;'>";
                        }
                    } else {
                         // Fallback: Se der erro na conversão, usa a original mesmo com fundo preto
                         $signature_img = "<img src='{$full_path_png}' style='width: 150px; max-height: 80px; display: block; margin: 0 auto;'>";
                    }
                }
            }
            
            // 2. Substituições
            $replaces = [];
            $replaces['{$id}'] = $object->id;
            $replaces['{$asset_name}'] = $asset->name;
            $replaces['{$asset_serial}'] = $asset->serial ?? 'N/A';
            $replaces['{$technician_name}'] = $technician->name;
            $replaces['{$created_at}'] = TDate::date2br($object->created_at);
            $replaces['{$status}'] = $object->status;
            $replaces['{$priority}'] = $object->priority;
            $replaces['{$description}'] = nl2br($object->description);
            $replaces['{$print_date}'] = date('d/m/Y H:i');
            
            $replaces['{$signature_img}'] = $signature_img;

            TTransaction::close();

            // 3. Geração do PDF
            $html_file = 'app/resources/maintenance_order.html';
            $content = file_get_contents($html_file);
            $content = str_replace(array_keys($replaces), array_values($replaces), $content);

            $temp_html = "tmp/os_{$key}.html";
            file_put_contents($temp_html, $content);

            $pdf_file = "tmp/os_{$key}.pdf";
            $parser = new AdiantiHTMLDocumentParser($temp_html);
            $parser->saveAsPDF($pdf_file);

            parent::openFile($pdf_file);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}