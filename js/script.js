// Configuração Global
const APP_CONFIG = {
    // Timeout padrão para operações (em ms)
    DEFAULT_TIMEOUT: 500,
    // Duração padrão para alertas (em ms)
    ALERT_DURATION: 5000
};

// Configurações do DataTables
const dataTablesConfig = {
    defaultOptions: {
        language: {
            url: 'js/pt-BR.json'
        },
        dom: function() {
            return document.body.getAttribute('data-user-profile') === 'Administrador' ? 'Bfrtip' : 'frtip';
        }(),
        buttons: [
            {
                extend: 'collection',
                text: '<i class="fas fa-download"></i> Exportar',
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i class="fas fa-copy"></i> Copiar',
                        className: 'btn btn-sm',
                        exportOptions: { columns: ':not(.no-export)' }
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-sm',
                        exportOptions: { columns: ':not(.no-export)' }
                    },
                    {
                        extend: 'csv',
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        className: 'btn btn-sm',
                        exportOptions: { columns: ':not(.no-export)' }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-sm',
                        exportOptions: { columns: ':not(.no-export)' }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn btn-sm',
                        exportOptions: { columns: ':not(.no-export)' }
                    }
                ],
                className: 'btn btn-secondary'
            }
        ],
        pageLength: 10,
        responsive: true,
        ordering: true,
        searching: true,
        info: true,
        stateSave: true
    },
    tableConfigs: {
        'tabelaUsuarios': {
            order: [[0, 'desc']]
        }
    }
};

// Gerenciador de DataTables
const DataTableManager = {
    init() {
        this.initTables();
        this.setupExportButtons();
    },

    initTables() {
        $('.datatable').each((index, table) => {
            const tableId = table.id;
            const specificConfig = dataTablesConfig.tableConfigs[tableId] || {};
            const finalConfig = { ...dataTablesConfig.defaultOptions, ...specificConfig };
            $(table).DataTable(finalConfig);
        });
    },

    setupExportButtons() {
        const tables = ['#tabelaUsuarios', '#tabelaProjetos', '#tabelaCustos'];
        
        tables.forEach(tableId => {
            const table = $(tableId);
            if (table.length) {
                table.on('init.dt', () => {
                    // Lógica de exportação aqui se necessário
                });
            }
        });
    }
};

// Gerenciador de Estado centralizado
const StateManager = {
    formStates: new Map(),
    
    setFormSubmitting(formId, state) {
        this.formStates.set(formId, state);
        
        // Adiciona timeout de segurança para reset
        setTimeout(() => {
            this.resetFormState(formId);
        }, 10000); // 10 segundos de timeout
    },
    
    isFormSubmitting(formId) {
        return this.formStates.get(formId) || false;
    },
    
    resetFormState(formId) {
        this.formStates.delete(formId);
    }
};

// Utilitários aprimorados
const Utils = {
    // Formatação de moeda
    formatMoney(value) {
        return value.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },

    // Validação de datas
    validateDates(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        return end > start;
    },

    // Cálculo de dias
    calcularDias(dataInicio, dataFim) {
        const inicio = new Date(dataInicio);
        const fim = new Date(dataFim);
        const diffTime = Math.abs(fim - inicio);
        return Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
    },

    // Cálculo de horas
    calcularHoras(dataInicio, dataFim) {
        const inicio = new Date(dataInicio);
        const fim = new Date(dataFim);
        
        if (inicio && fim && fim > inicio) {
            const diff = fim - inicio;
            return Math.round((diff / (1000 * 60 * 60)) * 100) / 100;
        }
        return 0;
    },

    // Manipulação de DOM
    DOM: {
        // Habilita/desabilita elemento
        toggleElement(element, enabled) {
            if (element) {
                element.disabled = !enabled;
            }
        },

        // Mostra/esconde elemento
        toggleVisibility(element, visible) {
            if (element) {
                element.style.display = visible ? '' : 'none';
            }
        },

        // Adiciona/remove classe
        toggleClass(element, className, add) {
            if (element) {
                element.classList[add ? 'add' : 'remove'](className);
            }
        }
    },

    // Validações
    Validators: {
        isEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        isDate(date) {
            return !isNaN(new Date(date).getTime());
        },

        isNumber(value) {
            return !isNaN(parseFloat(value)) && isFinite(value);
        }
    },

    // Formatadores
    Formatters: {
        dateBR(date) {
            return new Date(date).toLocaleDateString('pt-BR');
        },

        numberBR(number) {
            return Number(number).toLocaleString('pt-BR');
        },

        currencyBR(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }
    }
};

// Sistema de eventos personalizado
const EventBus = {
    listeners: {},

    on(event, callback) {
        if (!this.listeners[event]) {
            this.listeners[event] = [];
        }
        this.listeners[event].push(callback);
    },

    emit(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => callback(data));
        }
    },

    off(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event] = this.listeners[event]
                .filter(cb => cb !== callback);
        }
    }
};

// Gerenciador de Interface do Usuário
const UIManager = {
    init() {
        this.initSidebar();
        this.initAlerts();
        this.initTooltips();
        this.initPasswordToggles();

        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            const sidebar = {
                element: document.querySelector('.sidebar'),
                content: document.querySelector('#content'),
                navbar: document.querySelector('.top-navbar')
            };
            this.toggleSidebarDesktop(sidebar);
        }
    },

    initSidebar() {
        const sidebar = {
            element: document.querySelector('.sidebar'),
            content: document.querySelector('#content'),
            navbar: document.querySelector('.top-navbar'),
            toggleButton: document.querySelector('#sidebarCollapse'),
            toggleDesktopButton: document.querySelector('#sidebarCollapseDesktop')
        };

        if (sidebar.toggleDesktopButton) {
            sidebar.toggleDesktopButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSidebarDesktop(sidebar);
            });
        }

        if (sidebar.toggleButton) {
            sidebar.toggleButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSidebar(sidebar);
            });

            // Click fora para fechar no mobile
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    if (!e.target.closest('.sidebar') && 
                        !e.target.closest('#sidebarCollapse')) {
                        this.closeSidebar(sidebar);
                    }
                }
            });

            // Ajuste para resize da janela
            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) {
                    this.closeSidebar(sidebar);
                }
            });
        }
    },

    toggleSidebarDesktop(sidebar) {
        sidebar.element.classList.toggle('collapsed');
        sidebar.content.classList.toggle('sidebar-collapsed');
        sidebar.navbar.classList.toggle('sidebar-collapsed');
        
        // Salva estado no localStorage
        localStorage.setItem('sidebarCollapsed', 
            sidebar.element.classList.contains('collapsed'));
    },

    toggleSidebar(sidebar) {
        [sidebar.element, sidebar.content, sidebar.navbar]
            .forEach(el => el?.classList.toggle('active'));
    },

    closeSidebar(sidebar) {
        [sidebar.element, sidebar.content, sidebar.navbar]
            .forEach(el => el?.classList.remove('active'));
    },

    initAlerts() {
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            }, APP_CONFIG.ALERT_DURATION);
        });
    },

    initTooltips() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach(el => new bootstrap.Tooltip(el));
    },

    initPasswordToggles() {
        document.querySelectorAll('.toggle-password, .toggle-value').forEach(button => {
            button.addEventListener('click', () => this.handlePasswordToggle(button));
        });
    },

    handlePasswordToggle(button) {
        const targetId = button.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = button.querySelector('i');

        if (input && icon) {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.remove(isPassword ? 'fa-eye' : 'fa-eye-slash');
            icon.classList.add(isPassword ? 'fa-eye-slash' : 'fa-eye');
        }
    }
};

const FormManager = {
    init() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery não está carregado.');
            return;
        }

        this.initValidation();
        this.initMasks();
        this.initSubmitHandlers();
        /*this.initPasswordToggles();*/
    },

    initValidation() {
        document.querySelectorAll('.needs-validation').forEach(form => {
            form.addEventListener('submit', e => this.handleFormValidation(e, form));
        });
    },

    initMasks() {
        try {
            // Verifica se o plugin está disponível
            if (typeof jQuery.fn.mask !== 'undefined') {
                // Aplica máscaras monetárias
                jQuery('input[data-type="money"]').mask('#.##0,00', { reverse: true });
                
                // Máscaras específicas
                const masks = {
                    'valor_hora': '#.##0,00',
                    'valor_total': '#.##0,00',
                    'valor_diaria': '#.##0,00'
                };

                Object.entries(masks).forEach(([id, mask]) => {
                    const element = jQuery(`#${id}`);
                    if (element.length) {
                        element.mask(mask, { reverse: true });
                    }
                });
            }
        } catch (error) {
            console.error('Erro ao inicializar máscaras:', error);
        }
    },

    initSubmitHandlers() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', e => {
                e.preventDefault(); // Sempre previne o submit primeiro
    
                // Força a validação de todos os campos
                const inputs = form.querySelectorAll('input, select, textarea');
                let isValid = true;
    
                inputs.forEach(input => {
                    if (input.hasAttribute('required') && !input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                    }
                });
    
                // Adiciona classe para mostrar validações
                form.classList.add('was-validated');
    
                // Se não for válido, retorna sem fazer nada com o botão
                if (!form.checkValidity() || !isValid) {
                    return false;
                }
    
                // Se chegou aqui, o form está válido
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton && !submitButton.disabled) {
                    const formId = form.id || 'form_' + Math.random().toString(36).substr(2, 9);
                    form.id = formId;
    
                    if (!StateManager.isFormSubmitting(formId)) {
                        StateManager.setFormSubmitting(formId, true);
                        this.disableSubmitButton(submitButton);
                        form.submit();
                    }
                }
            });
        });
    },

    handleFormValidation(event, form) {
        event.preventDefault();
        form.classList.add('was-validated');
    
        // Se for um formulário de custo, valida também pelo CustoProjetoManager
        const isCustoForm = form.getAttribute('action')?.includes('custo_projeto');
        if (isCustoForm && window.CustoProjetoManager && !CustoProjetoManager.validateForm()) {
            return false;
        }
    
        if (!form.checkValidity()) {
            return false;
        }
    
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton && !submitButton.disabled) {
            this.disableSubmitButton(submitButton);
            form.submit();
        }
    },

    disableSubmitButton(button) {
        if (button && !button.disabled) {
            const originalText = button.innerHTML;
            button.disabled = true;
            button.setAttribute('data-original-text', originalText);
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Processando...
            `;
        }
    }

};

// CustoProjetoManager Atualizado
const CustoProjetoManager = {
    init() {
        this.form = document.querySelector('form[action*="custo_projeto"]');
        if (!this.form) return;

        this.initializeState();
        this.initializeEvents();
        this.calculateValues();

    },

    initializeState() {
        this.state = {
            valorHora: parseFloat(document.getElementById('valor_hora_usuario')?.value || '0'),
            tipoCusto: document.getElementById('tipo_custo')?.value || 'Horas'
        };

        this.updateInterface();
    },

    initializeEvents() {
        ['data_inicio', 'data_fim'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => this.calculateValues());
            }
        });

        const tipoCustoSelect = document.getElementById('tipo_custo');
        if (tipoCustoSelect) {
            tipoCustoSelect.addEventListener('change', () => {
                this.state.tipoCusto = tipoCustoSelect.value;
                this.updateInterface();
                this.calculateValues();
            });
        }

        const valorDiariaInput = document.getElementById('valor_diaria');
        if (valorDiariaInput) {
            valorDiariaInput.addEventListener('input', () => {
                if (this.state.tipoCusto === 'Diaria') {
                    this.calculateValues();
                }
            });
        }
    },

    validateForm() {
        // Este método agora só retorna o status da validação
        // sem manipular o submit ou o botão
        const dataInicio = new Date(document.getElementById('data_inicio')?.value || '');
        const dataFim = new Date(document.getElementById('data_fim')?.value || '');

        if (isNaN(dataInicio.getTime()) || isNaN(dataFim.getTime())) {
            return false;
        }

        if (dataFim <= dataInicio) {
            return false;
        }

        if (this.state.tipoCusto === 'Diaria') {
            const valorDiaria = this.parseMoneyValue(document.getElementById('valor_diaria')?.value);
            if (!valorDiaria || valorDiaria <= 0) {
                return false;
            }
        }

        return true;
    },

    updateInterface() {
        const isDiaria = this.state.tipoCusto === 'Diaria';
        const elements = {
            valorDiariaContainer: document.getElementById('valor_diaria_container'),
            horasSection: document.getElementById('horasSection'),
            diasSection: document.getElementById('diasSection'),
            valorPorTipoLabel: document.getElementById('valorPorTipoLabel')
        };

        if (elements.valorDiariaContainer) {
            elements.valorDiariaContainer.style.display = isDiaria ? 'block' : 'none';
        }
        if (elements.horasSection) {
            elements.horasSection.style.display = isDiaria ? 'none' : 'block';
        }
        if (elements.diasSection) {
            elements.diasSection.style.display = isDiaria ? 'block' : 'none';
        }
        if (elements.valorPorTipoLabel) {
            elements.valorPorTipoLabel.textContent = isDiaria ? 'Valor da Diária' : 'Valor por Hora';
        }
    },

    calculateValues() {
        const dataInicio = new Date(document.getElementById('data_inicio')?.value || '');
        const dataFim = new Date(document.getElementById('data_fim')?.value || '');

        if (isNaN(dataInicio.getTime()) || isNaN(dataFim.getTime()) || dataFim <= dataInicio) {
            this.updatePreview(0, 0, 0, 0);
            return;
        }

        if (this.state.tipoCusto === 'Diaria') {
            this.calculateDiariaValues(dataInicio, dataFim);
        } else {
            this.calculateHorasValues(dataInicio, dataFim);
        }
    },

    calculateDiariaValues(dataInicio, dataFim) {
        const dias = this.calcularDias(dataInicio, dataFim);
        const valorDiaria = this.parseMoneyValue(document.getElementById('valor_diaria')?.value || '0');
        const valorTotal = dias * valorDiaria;
        this.updatePreview(0, dias, valorTotal, valorDiaria);
    },

    calculateHorasValues(dataInicio, dataFim) {
        const horas = this.calcularHoras(dataInicio, dataFim);
        const valorTotal = horas * this.state.valorHora;
        this.updatePreview(horas, 0, valorTotal, this.state.valorHora);
    },

    calcularDias(dataInicio, dataFim) {
        if (dataInicio && dataFim) {
            // Extrai apenas a data (sem hora)
            const inicio = new Date(dataInicio).setHours(0, 0, 0, 0);
            const fim = new Date(dataFim).setHours(0, 0, 0, 0);
            
            // Se for o mesmo dia, retorna 1
            if (inicio === fim) {
                return 1;
            }
            
            // Caso contrário, calcula a diferença
            const diffTime = Math.abs(fim - inicio);
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        }
        return 0;
    },

    calcularHoras(dataInicio, dataFim) {
        const diffTime = Math.abs(dataFim - dataInicio);
        return Math.round((diffTime / (1000 * 60 * 60)) * 100) / 100;
    },

    updatePreview(horas, dias, valorTotal, valorUnitario) {
        const elements = {
            previewHoras: document.getElementById('preview_horas'),
            previewDias: document.getElementById('preview_dias'),
            previewValor: document.getElementById('preview_valor'),
            valorPorTipo: document.getElementById('valorPorTipo'),
            valorTotalHidden: document.getElementById('valor_total_hidden')
        };

        if (elements.previewHoras) {
            // Converte horas decimais para formato HH:MM
            const horasInteiras = Math.floor(horas);
            const minutos = Math.round((horas - horasInteiras) * 60);
            elements.previewHoras.textContent = `${horasInteiras}:${minutos.toString().padStart(2, '0')}h`;
        }
        if (elements.previewDias) {
            elements.previewDias.textContent = `${dias} dia(s)`;
        }
        if (elements.previewValor) {
            elements.previewValor.textContent = `R$ ${this.formatMoney(valorTotal)}`;
        }
        if (elements.valorPorTipo) {
            elements.valorPorTipo.textContent = `R$ ${this.formatMoney(valorUnitario)}`;
        }
        if (elements.valorTotalHidden) {
            elements.valorTotalHidden.value = valorTotal;
        }
    },

    parseMoneyValue(value) {
        if (!value) return 0;
        return parseFloat(value.replace(/\./g, '').replace(',', '.')) || 0;
    },

    formatMoney(value) {
        if (typeof value !== 'number') return '0,00';
        return value.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
};

// Gerenciador de Datas
const DateManager = {
    init() {
        this.initDateHandlers();
        this.initFilterDates();
    },

    initDateHandlers() {
        const dataInicio = document.getElementById('data_inicio');
        const dataFim = document.getElementById('data_fim');

        if (!dataInicio || !dataFim) return;

        const updateEndDate = () => {
            if (dataInicio.value) {
                dataFim.value = dataInicio.value;
                dataFim.dispatchEvent(new Event('change'));
            }
        };

        dataInicio.addEventListener('input', updateEndDate);
        dataInicio.addEventListener('change', updateEndDate);
    },

    initFilterDates() {
        const filterForm = document.getElementById('filterForm');
        if (!filterForm) return;

        const dataInicio = document.getElementById('data_inicio');
        const dataFim = document.getElementById('data_fim');

        if (dataInicio && dataFim) {
            this.setupDateValidation(dataInicio, dataFim);
        }
    },

    setupDateValidation(startDate, endDate) {
        startDate.addEventListener('change', () => {
            if (endDate.value && startDate.value > endDate.value) {
                alert('A data inicial não pode ser maior que a data final');
                startDate.value = endDate.value;
            }
        });

        endDate.addEventListener('change', () => {
            if (startDate.value && endDate.value < startDate.value) {
                alert('A data final não pode ser menor que a data inicial');
                endDate.value = startDate.value;
            }
        });
    }
};

const PasswordValidator = {
    init() {
        const form = document.querySelector('form');
        const senha = document.getElementById('senha');
        const confirmarSenha = document.getElementById('confirmar_senha');

        if (!form || !senha || !confirmarSenha) return;

        senha.addEventListener('input', () => this.validatePasswords(senha, confirmarSenha));
        confirmarSenha.addEventListener('input', () => this.validatePasswords(senha, confirmarSenha));

        form.addEventListener('submit', (e) => {
            if (!this.validatePasswords(senha, confirmarSenha)) {
                e.preventDefault();
            }
        });
    },

    validatePasswords(senha, confirmarSenha) {
        const feedbackElement = confirmarSenha.parentElement.parentElement.querySelector('.invalid-feedback');
        
        if (confirmarSenha.value && senha.value !== confirmarSenha.value) {
            confirmarSenha.classList.add('is-invalid');
            confirmarSenha.classList.remove('is-valid');
            feedbackElement.textContent = 'As senhas não coincidem.';
            return false;
        } else if (confirmarSenha.value) {
            confirmarSenha.classList.remove('is-invalid');
            confirmarSenha.classList.add('is-valid');
            return true;
        }
        
        return true;
    }
};

const PagamentoManager = {
    init() {
        this.initFormHandlers();
        this.initStatusHandlers();
        this.initFileHandlers();
        this.initCalculations();
        this.initDataPrevista();
    },

    initDataPrevista() {
        const dataPrevista = document.getElementById('data_prevista');
        if (dataPrevista) {
            // Define data mínima como hoje
            dataPrevista.min = new Date().toISOString().split('T')[0];
            
            // Sugere data prevista como 5 dias úteis
            let dataFutura = new Date();
            dataFutura.setDate(dataFutura.getDate() + 7); // +7 para considerar fins de semana
            dataPrevista.value = dataFutura.toISOString().split('T')[0];
        }
    },

    initFormHandlers() {
        const form = document.querySelector('form[action*="pagamento"]');
        if (!form) return;

        const solicitanteSelect = document.getElementById('solicitante_id');
        if (solicitanteSelect) {
            this.handleSolicitanteChange(solicitanteSelect);
        }
    },


    initFormHandlers() {
        const form = document.querySelector('form[action*="pagamento"]');
        if (!form) return;

        const solicitanteSelect = document.getElementById('solicitante_id');
        if (solicitanteSelect) {
            this.handleSolicitanteChange(solicitanteSelect);
        }
    },


    initStatusHandlers() {
        const statusSelect = document.getElementById('status');
        if (!statusSelect) return;

        statusSelect.addEventListener('change', () => {
            const nfContainer = document.getElementById('nota_fiscal_container');
            if (!nfContainer) return;

            const isNfRequired = statusSelect.value === 'Pendente NF';
            nfContainer.style.display = isNfRequired ? 'block' : 'none';
            
            const nfInput = document.getElementById('nota_fiscal');
            if (nfInput) {
                nfInput.required = isNfRequired;
            }
        });
    },

    initFileHandlers() {
        const fileInput = document.getElementById('nota_fiscal');
        if (!fileInput) return;

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Validar tamanho (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('O arquivo é muito grande. Tamanho máximo: 5MB');
                fileInput.value = '';
                return;
            }

            // Validar tipo
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('Tipo de arquivo não permitido. Use PDF, JPG ou PNG.');
                fileInput.value = '';
            }
        });
    },

    initCalculations() {
        document.addEventListener('change', (e) => {
            if (e.target.matches('input[name="custos[]"]')) {
                this.updateTotal();
            }
        });
    },

    handleSolicitanteChange(select) {
        select.addEventListener('change', () => {
            const solicitanteId = select.value;
            const mes = document.getElementById('mes')?.value;
            const ano = document.getElementById('ano')?.value;

            if (!solicitanteId || !mes || !ano) return;

            this.loadCustos(solicitanteId, mes, ano);
        });

        // Observar mudanças no período também
        ['mes', 'ano'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => {
                    if (select.value) {
                        this.loadCustos(select.value, 
                            document.getElementById('mes').value,
                            document.getElementById('ano').value);
                    }
                });
            }
        });
    },

    loadCustos(solicitanteId, mes, ano) {
        const container = document.getElementById('lista-custos');
        if (!container) return;

        container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Carregando...</div>';

        fetch(`buscar_custos_pagamento.php?solicitante_id=${solicitanteId}&mes=${mes}&ano=${ano}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                this.updateTotal();
                this.initSelectAll();
            })
            .catch(error => {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro ao carregar custos: ${error}
                    </div>`;
            });
    },

    updateTotal() {
        const checkboxes = document.querySelectorAll('input[name="custos[]"]:checked');
        const totalElement = document.getElementById('valor-total');
        if (!totalElement) return;

        let total = 0;
        checkboxes.forEach(checkbox => {
            const valorCell = checkbox.closest('tr')?.querySelector('td[data-valor]');
            if (valorCell) {
                total += parseFloat(valorCell.getAttribute('data-valor'));
            }
        });

        totalElement.textContent = total.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    },

    initSelectAll() {
        const checkAll = document.getElementById('check-all');
        if (!checkAll) return;

        checkAll.addEventListener('change', () => {
            document.querySelectorAll('input[name="custos[]"]')
                .forEach(checkbox => {
                    checkbox.checked = checkAll.checked;
                });
            this.updateTotal();
        });
    },

    validateForm() {
        const required = ['solicitante_id', 'ordem_pagamento', 'data_prevista'];
        let isValid = true;

        required.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (!field || !field.value.trim()) {
                isValid = false;
                field?.classList.add('is-invalid');
            }
        });

        // Validar se há custos selecionados
        const custosSelecionados = document.querySelectorAll('input[name="custos[]"]:checked');
        if (custosSelecionados.length === 0) {
            isValid = false;
            alert('Selecione pelo menos um custo para o pagamento');
        }

        return isValid;
    }
};

// Inicialização da Aplicação
const App = {
    init() {
        this.initializeManagers();
        this.initializeGlobalHandlers();
    },

    initializeManagers() {
        UIManager.init();
        FormManager.init();
        DataTableManager.init();
        CustoProjetoManager.init();
        PagamentoManager.init();
        DateManager.init();
        PasswordValidator.init();

        // Emite evento de inicialização completa
        EventBus.emit('app:initialized');
    },

    initializeGlobalHandlers() {
        // Handler global para exclusão
        window.confirmarExclusao = (id, tipo) => {
            if (confirm(`Tem certeza que deseja excluir este ${tipo}?`)) {
                window.location.href = `index.php?modulo=excluir_${tipo.replace('usuário', 'usuario')}&id=${id}`;
            }
        };
    }
};

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => App.init());


        // Validação do formulário
        (function() {
            'use strict';
            
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // Toggle de senha
        document.getElementById('togglePassword').addEventListener('click', function() {
            const senhaInput = document.getElementById('senha');
            const icon = this.querySelector('i');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Auto-close para alertas
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }, 5000);
        });

// Adicionar ao seu script.js
$(document).ready(function() {
    // Seleção de todos os checkboxes
    $('#check-all').change(function() {
        $('.check-pagamento').prop('checked', $(this).is(':checked'));
        atualizarBotaoAprovar();
    });

    // Atualiza botão quando checkbox individual é alterado
    $('.check-pagamento').change(function() {
        atualizarBotaoAprovar();
    });

    // Botão de aprovar selecionados
    $('#btnAprovarSelecionados').click(function() {
        const pagamentosSelecionados = [];
        $('.check-pagamento:checked').each(function() {
            pagamentosSelecionados.push($(this).val());
        });

        if (pagamentosSelecionados.length === 0) {
            alert('Selecione pelo menos um pagamento para aprovar');
            return;
        }

        if (confirm('Deseja aprovar os pagamentos selecionados?')) {
            $.ajax({
                url: 'aprovar_pagamentos.php',
                method: 'POST',
                data: {
                    pagamentos: JSON.stringify(pagamentosSelecionados)
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        window.location.reload();
                    } else {
                        alert('Erro: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erro ao processar a requisição');
                }
            });
        }
    });

    function atualizarBotaoAprovar() {
        const temSelecionados = $('.check-pagamento:checked').length > 0;
        $('#btnAprovarSelecionados').prop('disabled', !temSelecionados);
    }
});

