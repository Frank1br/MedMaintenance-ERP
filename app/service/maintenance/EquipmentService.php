<?php

class EquipmentService
{
    /**
     * Valida se um equipamento pode receber uma nova Ordem de Serviço.
     * @param int $asset_id O ID do equipamento
     * @param int $current_os_id (Opcional) O ID da OS atual, para ignorar na busca se for uma edição
     */
    public static function validateMaintenanceRequest($asset_id, $current_os_id = null)
    {
        TTransaction::open('med_maintenance');
        
        try {
            $asset = new Asset($asset_id);
            
            if (empty($asset->id)) {
                throw new Exception("Equipamento (ID: {$asset_id}) não encontrado.");
            }

            // Regra 1: Bloqueia se o Equipamento estiver BAIXADO
            if ($asset->status == 'BAIXADO') {
                throw new Exception("BLOQUEIO: O equipamento '{$asset->name}' consta como BAIXADO. Não é possível abrir chamados.");
            }

            // Regra 2: Verifica se já existe OS aberta (Concorrência)
            $repository = new TRepository('MaintenanceOrder');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('asset_id', '=', $asset_id));
            $criteria->add(new TFilter('status', '<>', 'FECHADA'));
            $criteria->add(new TFilter('status', '<>', 'CANCELADA'));
            
            // --- A CORREÇÃO: Ignora a OS atual se estivermos editando ---
            if (!empty($current_os_id)) {
                $criteria->add(new TFilter('id', '<>', $current_os_id)); 
            }
            
            $count = $repository->count($criteria);

            if ($count > 0) {
                throw new Exception("ALERTA: Já existe uma Ordem de Serviço aberta para este equipamento. Verifique a lista de pendências.");
            }
            
            return true;
            
        } catch (Exception $e) {
            throw $e; // Repassa o erro para a tela
        } finally {
            TTransaction::close();
        }
    }
}
?>