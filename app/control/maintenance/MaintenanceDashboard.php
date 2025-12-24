<?php
/**
 * MaintenanceDashboard
 * Painel de Indicadores (Versão Final: Ajustado para Dark Mode e Dados Reais)
 */
class MaintenanceDashboard extends TPage
{
    public function __construct()
    {
        parent::__construct();
        
        $this->setTargetContainer('adianti_div_content');

        $main_box = new TVBox;
        $main_box->style = 'width: 100%';

        $main_box->add(new TLabel('Dashboard de Gestão da Manutenção', '#999', '18px', 'B')); // Cor mais clara
        $main_box->add(new TElement('hr'));

        // --- 1. COLETA DE DADOS ---
        try {
            TTransaction::open('med_maintenance');
            
            $count_assets  = Asset::count(); 
            $count_open_os = MaintenanceOrder::where('status', '!=', 'FECHADA')->count();
            $count_techs   = Technician::where('active', '=', 'Y')->count();

            // GRÁFICO 1: STATUS GERAL (Para não ficar vazio quando tudo está fechado)
            // Agrupa por status (ABERTA, FECHADA, EM ANDAMENTO)
            $os_by_status = MaintenanceOrder::groupBy('status')
                                            ->countBy('id', 'total');

            // GRÁFICO 2: TOP TÉCNICOS
            $conn = TTransaction::get();
            $query_tech = "SELECT t.name, count(m.id) as total 
                        FROM maintenance_orders m, technicians t 
                        WHERE m.technician_id = t.id 
                        GROUP BY t.name 
                        ORDER BY total DESC LIMIT 5";
            $result_tech = $conn->query($query_tech);

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            return;
        }

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

        $row_indicators->add( $create_box('fa:cubes', 'aqua', 'Total Equipamentos', $count_assets, 'Cadastrados no sistema') );
        $row_indicators->add( $create_box('fa:exclamation-triangle', 'yellow', 'OS Pendentes', $count_open_os, 'Aguardando ação') );
        $row_indicators->add( $create_box('fa:user-md', 'green', 'Técnicos Ativos', $count_techs, 'Equipe disponível') );

        $main_box->add($row_indicators);

        // --- 3. ARRAYS PARA O GRÁFICO ---
        
        // Gráfico 1: Status
        $s_labels = []; $s_data = []; $s_colors = [];
        if ($os_by_status) {
            foreach ($os_by_status as $row) {
                $s_labels[] = $row->status;
                $s_data[]   = (int) $row->total;
                
                // Cores baseadas no Status
                if ($row->status == 'FECHADA') $s_colors[] = '#00a65a'; // Verde
                elseif ($row->status == 'ABERTA')  $s_colors[] = '#f39c12'; // Laranja
                else $s_colors[] = '#00c0ef'; // Azul (Outros)
            }
        }
        if (empty($s_data)) { $s_labels[]='Sem dados'; $s_data[]=1; $s_colors[]='#555'; }

        // Gráfico 2: Técnicos
        $t_labels = []; $t_data = [];
        if ($result_tech) {
            foreach ($result_tech as $row) {
                $t_labels[] = $row['name'];
                $t_data[]   = (int) $row['total'];
            }
        }

        $json_s_labels = json_encode($s_labels);
        $json_s_data   = json_encode($s_data);
        $json_s_colors = json_encode($s_colors);
        
        $json_t_labels = json_encode($t_labels);
        $json_t_data   = json_encode($t_data);

        // --- 4. HTML (CORRIGIDO PARA DARK MODE) ---
        // Alterei a cor dos títulos (color: inherit) para se adaptar ao tema
        
        $html_charts = "
        <div class='row'>
            <div class='col-md-6'>
                <div class='card card-default'>
                    <div class='card-header'>
                        <h3 class='card-title' style='font-weight:bold; color: inherit'>Status das Ordens</h3>
                    </div>
                    <div class='card-body'>
                        <canvas id='donutChart' style='min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;'></canvas>
                    </div>
                </div>
            </div>

            <div class='col-md-6'>
                <div class='card card-default'>
                    <div class='card-header'>
                        <h3 class='card-title' style='font-weight:bold; color: inherit'>Produtividade Técnica</h3>
                    </div>
                    <div class='card-body'>
                        <canvas id='barChart' style='min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;'></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $(function () {
                // DONUT (Status)
                var donutCanvas = $('#donutChart').get(0).getContext('2d');
                new Chart(donutCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: $json_s_labels,
                        datasets: [{
                            data: $json_s_data,
                            backgroundColor : $json_s_colors,
                        }]
                    },
                    options: { 
                        maintainAspectRatio: false, 
                        responsive: true,
                        legend: { labels: { fontColor: '#999' } } // Legenda clara
                    }
                });

                // BAR (Técnicos)
                var barCanvas = $('#barChart').get(0).getContext('2d');
                new Chart(barCanvas, {
                    type: 'bar',
                    data: {
                        labels: $json_t_labels,
                        datasets: [{
                            label: 'Qtd. Ordens',
                            backgroundColor: '#3c8dbc',
                            borderColor: '#3c8dbc',
                            data: $json_t_data
                        }]
                    },
                    options: {
                        maintainAspectRatio: false, 
                        responsive: true,
                        legend: { labels: { fontColor: '#999' } },
                        scales: {
                            yAxes: [{ ticks: { beginAtZero: true, fontColor: '#999' } }],
                            xAxes: [{ ticks: { fontColor: '#999' } }]
                        }
                    }
                });
            })
        </script>
        ";

        $main_box->add(new TLabel($html_charts));
        parent::add($main_box);
    }
}
?>