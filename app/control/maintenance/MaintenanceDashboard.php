<?php
/**
 * MaintenanceDashboard
 * Painel de Indicadores da Manutenção (Versão Nativa - Chart.js)
 * @author Tech Lead (Gemini)
 */
class MaintenanceDashboard extends TPage
{
    public function __construct()
    {
        parent::__construct();
        
        $this->setTargetContainer('adianti_div_content');

        // Container principal
        $main_box = new TVBox;
        $main_box->style = 'width: 100%';

        // Título
        $main_box->add(new TLabel('Painel de Controle da Manutenção', '#333', '18px', 'B'));
        $main_box->add(new TElement('hr'));

        // --- 1. COLETA DE DADOS ---
        TTransaction::open('med_maintenance');
        
        $count_assets  = Asset::where('status', '=', 'OPERACIONAL')->count();
        $count_open_os = MaintenanceOrder::where('status', '=', 'ABERTA')->count();
        $count_techs   = Technician::where('active', '=', 'Y')->count();

        // Dados para o Gráfico
        $os_by_priority = MaintenanceOrder::where('status', '=', 'ABERTA')
                                          ->groupBy('priority')
                                          ->countBy('id', 'total');
                                          
        TTransaction::close();

        // --- 2. OS CARTÕES (KPIs) ---
        $row_indicators = new TElement('div');
        $row_indicators->class = 'row';
        $row_indicators->style = 'margin-bottom: 20px; margin-top: 20px';

        $create_box = function($icon, $class_color, $title, $value, $text) {
            $col = new TElement('div');
            $col->class = 'col-sm-4';

            $box = new TElement('div');
            $box->class = 'info-box';
            
            $icon_span = new TElement('span');
            $icon_span->class = "info-box-icon bg-{$class_color}";
            $i_elem = new TElement('i');
            $i_elem->class = $icon;
            $icon_span->add($i_elem);
            
            $content = new TElement('div');
            $content->class = 'info-box-content';
            
            $text_span = new TElement('span');
            $text_span->class = 'info-box-text';
            $text_span->add($title);
            
            $number_span = new TElement('span');
            $number_span->class = 'info-box-number';
            $number_span->add($value);
            
            $desc_span = new TElement('span');
            $desc_span->class = 'progress-description';
            $desc_span->style = 'font-size: 12px; color: #999';
            $desc_span->add($text);
            
            $content->add($text_span);
            $content->add($number_span);
            $content->add($desc_span);
            
            $box->add($icon_span);
            $box->add($content);
            $col->add($box);
            return $col;
        };

        $row_indicators->add( $create_box('fa:heartbeat', 'success', 'Equipamentos OK', $count_assets, 'Em operação') );
        $row_indicators->add( $create_box('fa:wrench', 'warning', 'Chamados Abertos', $count_open_os, 'Aguardando solução') );
        $row_indicators->add( $create_box('fa:user-md', 'primary', 'Equipe Técnica', $count_techs, 'Técnicos ativos') );

        $main_box->add($row_indicators);

        // --- 3. O GRÁFICO (CHART.JS NATIVO) ---
        
        // Preparando Arrays PHP para o JavaScript
        $labels = [];
        $data   = [];
        $colors = [];
        
        if ($os_by_priority) {
            foreach ($os_by_priority as $row) {
                $labels[] = $row->priority;
                $data[]   = (int) $row->total;
                
                // Cores do Tema (Iguais aos badges)
                if ($row->priority == 'BAIXA')   $colors[] = '#28a745'; // Verde
                if ($row->priority == 'MEDIA')   $colors[] = '#f39c12'; // Laranja
                if ($row->priority == 'ALTA')    $colors[] = '#f56954'; // Vermelho Claro
                if ($row->priority == 'URGENTE') $colors[] = '#d33724'; // Vermelho Escuro
            }
        }

        // Se vazio, mostra cinza
        if (empty($data)) {
            $labels[] = 'Sem dados';
            $data[]   = 1;
            $colors[] = '#d2d6de'; // Cinza
        }

        $json_labels = json_encode($labels);
        $json_data   = json_encode($data);
        $json_colors = json_encode($colors);

        // HTML do Gráfico (Estrutura de Card do AdminLTE)
        $html_template = "
        <div class='card card-default'>
            <div class='card-header'>
                <h3 class='card-title' style='font-weight:bold; color:#555'>Prioridade dos Chamados Abertos</h3>
            </div>
            <div class='card-body'>
                <canvas id='donutChart' style='min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;'></canvas>
            </div>
        </div>
        <script>
            $(function () {
                var donutChartCanvas = $('#donutChart').get(0).getContext('2d');
                var donutData = {
                    labels: $json_labels,
                    datasets: [
                        {
                            data: $json_data,
                            backgroundColor : $json_colors,
                        }
                    ]
                }
                var donutOptions = {
                    maintainAspectRatio : false,
                    responsive : true,
                    legend: {
                        position: 'right'
                    }
                }
                
                // Renderiza o gráfico usando a biblioteca nativa
                new Chart(donutChartCanvas, {
                    type: 'doughnut',
                    data: donutData,
                    options: donutOptions
                });
            })
        </script>
        ";

        // Adiciona o HTML ao container
        $main_box->add(new TLabel($html_template));

        parent::add($main_box);
    }
}
?>