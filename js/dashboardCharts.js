        /**
 * Dashboard JavaScript - Sistema AugeBit
 * Responsável pelos gráficos e funcionalidades interativas do dashboard
 */

class DashboardCharts {
    constructor() {
        this.charts = {};
        this.defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#667eea',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true
                }
            }
        };
        
        this.colors = {
            primary: '#fff',
            secondary: '#764ba2',
            success: '#43e97b',
            warning: '#fa709a',
            info: '#4facfe',
            light: '#000',
            gradients: {
                primary: 'linear-gradient(135deg, #667eea, #764ba2)',
                success: 'linear-gradient(135deg, #43e97b, #38f9d7)',
                warning: 'linear-gradient(135deg, #fa709a, #fee140)',
                info: 'linear-gradient(135deg, #4facfe, #00f2fe)'
            }
        };
        
        this.init();
    }
    
    init() {
        // Configurações padrão do Chart.js
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#666';
        Chart.defaults.borderColor = 'rgba(0,0,0,0.1)';
        
        // Aguardar o DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initCharts());
        } else {
            this.initCharts();
        }
    }
    
    initCharts() {
        try {
            this.createProgressChart();
            this.createTaskStatusChart();
            this.createProjectStatusChart();
            this.createPerformanceChart();
        } catch (error) {
            console.error('Erro ao inicializar gráficos:', error);
        }
    }
    
    createProgressChart() {
        const ctx = document.getElementById('progressoMensalChart');
        if (!ctx || !window.progressoMensalData) return;
        
        const data = window.progressoMensalData;
        
        this.charts.progresso = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => this.formatMonth(item.mes)),
                datasets: [{
                    label: 'Projetos Criados',
                    data: data.map(item => item.total_projetos),
                    borderColor: this.colors.primary,
                    backgroundColor: this.hexToRgba(this.colors.primary, 0.1),
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: this.colors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }, {
                    label: 'Projetos Concluídos',
                    data: data.map(item => item.projetos_concluidos),
                    borderColor: this.colors.success,
                    backgroundColor: this.hexToRgba(this.colors.success, 0.1),
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: this.colors.success,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                ...this.defaultOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                },
                plugins: {
                    ...this.defaultOptions.plugins,
                    title: {
                        display: false
                    }
                }
            }
        });
    }
    
    createTaskStatusChart() {
        const ctx = document.getElementById('statusTarefasChart');
        if (!ctx || !window.taxaConclusaoData) return;
        
        const data = window.taxaConclusaoData;
        const colors = [
            this.colors.primary,
            this.colors.success,
            this.colors.warning,
            this.colors.info,
            this.colors.light
        ];
        
        this.charts.tarefas = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => this.formatStatus(item.status)),
                datasets: [{
                    data: data.map(item => item.total),
                    backgroundColor: colors.slice(0, data.length),
                    borderWidth: 0,
                    hoverBorderWidth: 2,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                ...this.defaultOptions,
                cutout: '60%',
                plugins: {
                    ...this.defaultOptions.plugins,
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }
    
    createProjectStatusChart() {
        const ctx = document.getElementById('statusProjetosChart');
        if (!ctx || !window.dadosProjetos) return;
        
        const data = window.dadosProjetos;
        const labels = Object.keys(data).map(status => this.formatStatus(status));
        const values = Object.values(data);
        
        const colors = [
            this.colors.primary,
            this.colors.success,
            this.colors.warning,
            this.colors.info
        ];
        this.charts.projetos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Quantidade',
                    data: values,
                    backgroundColor: colors.slice(0, labels.length),
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 50
                }]
            },
            options: {
                ...this.defaultOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                },
                plugins: {
                    ...this.defaultOptions.plugins,
                    title: {
                        display: true,
                        text: 'Status dos Projetos',
                        font: {
                            size: 16
                        },
                        color: '#333'
                    }
                }
            }
        });
    }

    createPerformanceChart() {
        // (Opcional) Exemplo de gráfico extra de desempenho por funcionário
        const ctx = document.getElementById('desempenhoChart');
        if (!ctx || !window.dadosFuncionarios) return;

        const data = window.dadosFuncionarios;
        const labels = data.map(item => item.nome);
        const values = data.map(item => item.concluidas);

        this.charts.desempenho = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Tarefas Concluídas',
                    data: values,
                    backgroundColor: this.colors.success,
                    borderRadius: 10,
                    maxBarThickness: 40
                }]
            },
            options: {
                ...this.defaultOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                }
            }
        });
    }

    // Utilitários
    formatMonth(mes) {
        const nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        return nomes[parseInt(mes) - 1] || mes;
    }

    formatStatus(status) {
        return status
            .replace('_', ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }

    hexToRgba(hex, alpha) {
        const r = parseInt(hex.substring(1, 3), 16);
        const g = parseInt(hex.substring(3, 5), 16);
        const b = parseInt(hex.substring(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
}

// Instanciar a classe ao carregar a página
new DashboardCharts();
