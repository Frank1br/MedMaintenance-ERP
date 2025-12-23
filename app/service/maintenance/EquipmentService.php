<?php
// Importante: Não usamos namespaces complexos aqui para facilitar o autoload padrão do Adianti
// mas em projetos grandes, usaríamos App\Service\Maintenance;

class EquipmentService
{
    /**
     * Valida se um equipamento pode receber uma nova Ordem de Serviço.
     * * @param int $asset_id O ID do equipamento
     * @throws Exception Se houver qualquer impedimento de negócio
     */
    public static function validateMaintenanceRequest($asset_id)
    {
        // 1. Abre conexão com o banco (TTransaction é vital no Adianti)
        TTransaction::open('med_maintenance');
        
        try {
            // Busca o equipamento usando o Model que criamos antes
            $asset = new Asset($asset_id);
            
            // Regra 0: O equipamento existe?
            if (empty($asset->id)) {
                throw new Exception("Equipamento (ID: {$asset_id}) não encontrado no banco de dados.");
            }

            // Regra de Negócio 1: Equipamento Baixado/Descartado
            // Na saúde, é proibido gastar recurso em ativo baixado.
            if ($asset->status == 'BAIXADO') {
                throw new Exception("BLOQUEIO: O equipamento '{$asset->name}' consta como BAIXADO. Não é possível abrir chamados.");
            }

            // Regra de Negócio 2: Concorrência de Manutenção
            // Verifica se já existe OS aberta (Status != FECHADA)
            $repository = new TRepository('MaintenanceOrder');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('asset_id', '=', $asset_id));
            $criteria->add(new TFilter('status', '<>', 'FECHADA')); // Qualquer coisa que não esteja fechada
            $criteria->add(new TFilter('status', '<>', 'CANCELADA')); 
            
            $count = $repository->count($criteria);

            if ($count > 0) {
                // Aqui poderíamos retornar apenas um aviso, mas para ser rigoroso, vamos bloquear.
                throw new Exception("ALERTA: Já existe uma Ordem de Serviço aberta para este equipamento. Verifique a lista de pendências.");
            }
            
            // Se chegou aqui, o equipamento está apto!
            return true;
            
        } catch (Exception $e) {
            // Repassa o erro para quem chamou (o Controller vai exibir o Toast vermelho)
            throw $e; 
        } finally {
            // Fecha conexão (boas práticas)
            TTransaction::close();
        }
    }

    /**
     * Verifica garantia (Regra Informativa)
     * Não bloqueia, mas avisa.
     */
    public static function checkWarranty($asset_id)
    {
        TTransaction::open('med_maintenance');
        $asset = new Asset($asset_id);
        TTransaction::close();

        if ($asset->warranty_expires_at) {
            $hoje = new DateTime();
            $garantia = new DateTime($asset->warranty_expires_at);
            
            if ($garantia >= $hoje) {
                return [
                    'in_warranty' => true,
                    'message' => "⚠️ ATENÇÃO: Equipamento em GARANTIA até " . $garantia->format('d/m/Y') . ". Contate o fabricante antes de abrir."
                ];
            }
        }
        
        return ['in_warranty' => false, 'message' => ''];
    }
}
?>