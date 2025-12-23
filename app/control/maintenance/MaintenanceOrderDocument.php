<?php
/**
 * MaintenanceOrderDocument
 * Gera PDF da Ordem de Serviço
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
            // 1. Obtém o ID da OS
            $key = $param['id'];
            if (!$key) throw new Exception("ID da OS não informado.");

            // 2. Busca os dados
            TTransaction::open('med_maintenance');
            $object = new MaintenanceOrder($key);
            
            if (!$object) throw new Exception("OS não encontrada.");

            $asset = new Asset($object->asset_id);
            $technician = new Technician($object->technician_id);

            // 3. Prepara os dados
            $replaces = [];
            $replaces['{$id}'] = $object->id;
            $replaces['{$asset_name}'] = $asset->name;
            $replaces['{$asset_serial}'] = isset($asset->serial) ? $asset->serial : 'N/A';
            $replaces['{$technician_name}'] = $technician->name;
            $replaces['{$created_at}'] = TDate::date2br($object->created_at);
            $replaces['{$status}'] = $object->status;
            $replaces['{$priority}'] = $object->priority;
            $replaces['{$description}'] = nl2br($object->description);
            $replaces['{$print_date}'] = date('d/m/Y H:i');

            TTransaction::close();

            // 4. Lê o HTML Modelo
            $html_file = 'app/resources/maintenance_order.html';
            if (!file_exists($html_file)) {
                throw new Exception("Arquivo de modelo não encontrado: $html_file");
            }
            $content = file_get_contents($html_file);

            // 5. Substitui as variáveis
            $content = str_replace(array_keys($replaces), array_values($replaces), $content);

            // 6. Salva HTML temporário
            $temp_html = "tmp/os_{$key}.html";
            file_put_contents($temp_html, $content);

            // 7. Gera o PDF
            $pdf_file = "tmp/os_{$key}.pdf";
            $parser = new AdiantiHTMLDocumentParser($temp_html);
            $parser->saveAsPDF($pdf_file);

            // 8. Abre o arquivo (MÉTODO CORRIGIDO)
            // Isso avisa o navegador para baixar ou abrir o PDF de forma segura
            parent::openFile($pdf_file);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}