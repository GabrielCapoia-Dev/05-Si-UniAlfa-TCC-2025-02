<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transporte Escolar Municipal - Umuarama - Educação com Segurança</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
</head>

<body>
    <!-- Header -->
    <header class="header" id="header">
        <nav class="nav">
            <div class="logo">
                <div class="logo-icon">🚌</div>
                <div class="logo-text">
                    <div class="logo-title">Transporte Escolar</div>
                    <div class="logo-subtitle">Prefeitura de Umuarama</div>
                </div>
            </div>
            <a href="/admin/login" class="login-btn">
                <span>🔐</span>
                Área Administrativa
            </a>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Transporte Escolar <span class="highlight">Municipal</span> de Umuarama</h1>
                    <p>Sistema integrado de gestão do transporte escolar municipal, garantindo segurança, eficiência e
                        qualidade no atendimento aos nossos estudantes.</p>

                    <div class="hero-badges">
                        <div class="badge">
                            <span>🛡️</span>
                            Segurança Certificada
                        </div>
                        <div class="badge">
                            <span>📱</span>
                            Gestão Digital
                        </div>
                        <div class="badge">
                            <span>🌱</span>
                            Sustentável
                        </div>
                    </div>

                    <div class="cta-buttons">
                        <a href="#servicos" class="btn-primary">
                            <span>📋</span>
                            Nossos Serviços
                        </a>
                        <a href="#escolas" class="btn-secondary">
                            <span>🏫</span>
                            Escolas Atendidas
                        </a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-image">
                        <div class="floating-elements">
                            <div class="floating-element">🎒</div>
                            <div class="floating-element">📚</div>
                            <div class="floating-element">🏫</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card fade-in">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number">3.200+</div>
                    <div class="stat-label">Estudantes Atendidos</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-icon">🚌</div>
                    <div class="stat-number">58</div>
                    <div class="stat-label">Veículos na Frota</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-icon">🏫</div>
                    <div class="stat-number">42</div>
                    <div class="stat-label">Instituições de Ensino</div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-icon">🛣️</div>
                    <div class="stat-number">125</div>
                    <div class="stat-label">Rotas Ativas</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="servicos">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Nossos Serviços</h2>
                <p class="section-subtitle">Oferecemos um sistema completo de transporte escolar com foco na segurança,
                    pontualidade e bem-estar dos nossos estudantes.</p>
            </div>

            <div class="services-grid">
                <div class="service-card fade-in">
                    <div class="service-header">
                        <div class="service-icon">🚌</div>
                        <h3 class="service-title">Transporte Regular</h3>
                    </div>
                    <p class="service-description">Atendimento diário aos estudantes da rede municipal e estadual, com
                        rotas otimizadas e horários rigorosamente cumpridos.</p>
                    <ul class="service-features">
                        <li>Rotas urbanas e rurais</li>
                        <li>Motoristas habilitados e treinados</li>
                        <li>Manutenção preventiva rigorosa</li>
                        <li>Monitores em todos os veículos</li>
                    </ul>
                </div>

                <div class="service-card fade-in">
                    <div class="service-header">
                        <div class="service-icon">📱</div>
                        <h3 class="service-title">Sistema de Gestão</h3>
                    </div>
                    <p class="service-description">Plataforma digital completa para controle e monitoramento de toda a
                        operação do transporte escolar municipal.</p>
                    <ul class="service-features">
                        <li>Rastreamento GPS em tempo real</li>
                        <li>Controle de estudantes por veículo</li>
                        <li>Relatórios gerenciais completos</li>
                        <li>Integração com escolas</li>
                    </ul>
                </div>

                <div class="service-card fade-in">
                    <div class="service-header">
                        <div class="service-icon">🎓</div>
                        <h3 class="service-title">Transporte Especial</h3>
                    </div>
                    <p class="service-description">Atendimento especializado para estudantes com necessidades especiais
                        e atividades extracurriculares.</p>
                    <ul class="service-features">
                        <li>Veículos adaptados</li>
                        <li>Profissionais especializados</li>
                        <li>Atendimento personalizado</li>
                        <li>Parceria com famílias</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Schools Section -->
    <section class="schools" id="escolas">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Instituições Atendidas</h2>
                <p class="section-subtitle">Atendemos todas as principais escolas municipais, estaduais e instituições
                    de ensino da região de Umuarama</p>
            </div>

            <div class="schools-grid">
                <div class="school-card fade-in">
                    <h3 class="school-name">EMEI Pequeno Mundo</h3>
                    <p class="school-info">Educação Infantil - Zona Norte</p>
                    <span class="school-students">142 estudantes</span>
                </div>

                <div class="school-card fade-in">
                    <h3 class="school-name">Escola Municipal Prof. João Batista</h3>
                    <p class="school-info">Ensino Fundamental - Centro</p>
                    <span class="school-students">298 estudantes</span>
                </div>

                <div class="school-card fade-in">
                    <h3 class="school-name">Colégio Estadual Humberto Alencar</h3>
                    <p class="school-info">Ensino Médio - Zona Sul</p>
                    <span class="school-students">387 estudantes</span>
                </div>

                <div class="school-card fade-in">
                    <h3 class="school-name">EMEF Vila Operária</h3>
                    <p class="school-info">Ensino Fundamental - Vila Operária</p>
                    <span class="school-students">156 estudantes</span>
                </div>

                <div class="school-card fade-in">
                    <h3 class="school-name">Escola Rural São José</h3>
                    <p class="school-info">Multisseriada - Zona Rural</p>
                    <span class="school-students">89 estudantes</span>
                </div>

                <div class="school-card fade-in">
                    <h3 class="school-name">APAE Umuarama</h3>
                    <p class="school-info">Educação Especial - Centro</p>
                    <span class="school-students">73 estudantes</span>
                </div>

                <div class="school-card fade-in">
                    <h3 class="school-name">Colégio Agrícola Estadual</h3>
                    <p class="school-info">Ensino Técnico - Zona Rural</p>
                    <span class="school-students">234 estudantes</span>
                </div>

                <div class="school-card fade-in">
                    <h3 class="school-name">EMEI Jardim América</h3>
                    <p class="school-info">Educação Infantil - Jardim América</p>
                    <span class="school-students">118 estudantes</span>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Gestão Inteligente do Transporte Escolar</h2>
                <p>Acesse nossa plataforma administrativa para gerenciar rotas, monitorar veículos, controlar estudantes
                    e gerar relatórios completos do sistema de transporte escolar municipal.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Prefeitura Municipal de Umuarama</h3>
                    <p>Departamento de Transporte Escolar</p>
                    <p>Garantindo educação com segurança e qualidade para todos os nossos estudantes.</p>
                </div>

                <div class="footer-section">
                    <h3>Contato</h3>
                    <p>📞 (44) 3621-3500</p>
                    <p>📧 transporte.escolar@umuarama.pr.gov.br</p>
                    <p>📍 Av. Rio Branco, 4500 - Centro</p>
                    <p>Umuarama - PR, 87501-130</p>
                </div>

                <div class="footer-section">
                    <h3>Horário de Atendimento</h3>
                    <p>Segunda a Sexta: 7h às 17h</p>
                    <p>Almoço: 11h30 às 13h30</p>
                    <p>Emergências: 24h</p>
                    <p>Telefone de Emergência: (44) 99999-0000</p>
                </div>

                <div class="footer-section">
                    <h3>Links Úteis</h3>
                    <p><a href="https://umuarama.pr.gov.br">Portal da Prefeitura</a></p>
                    <p><a href="#">Secretaria de Educação</a></p>
                    <p><a href="#">Ouvidoria Municipal</a></p>
                    <p><a href="#">Portal da Transparência</a></p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 Prefeitura Municipal de Umuarama. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all fade-in elements
        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Add some interactive hover effects
        document.querySelectorAll('.stat-card, .service-card, .school-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Add floating animation to hero elements
        const floatingElements = document.querySelectorAll('.floating-element');
        floatingElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 2}s`;
        });

        // Counter animation for stats
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');

            counters.forEach(counter => {
                const target = parseInt(counter.textContent.replace(/\D/g, ''));
                const increment = target / 100;
                let current = 0;

                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        if (counter.textContent.includes('+')) {
                            counter.textContent = Math.floor(current).toLocaleString('pt-BR') + '+';
                        } else {
                            counter.textContent = Math.floor(current).toLocaleString('pt-BR');
                        }
                        requestAnimationFrame(updateCounter);
                    } else {
                        if (counter.textContent.includes('+')) {
                            counter.textContent = target.toLocaleString('pt-BR') + '+';
                        } else {
                            counter.textContent = target.toLocaleString('pt-BR');
                        }
                    }
                };

                // Start animation when stats section comes into view
                const statsSection = document.querySelector('.stats');
                const statsObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            setTimeout(updateCounter, Math.random() * 500);
                            statsObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.5
                });

                statsObserver.observe(statsSection);
            });
        }

        // Initialize counter animation
        animateCounters();

        // Add dynamic background pattern
        function createBackgroundPattern() {
            const hero = document.querySelector('.hero');
            const pattern = document.createElement('div');
            pattern.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                opacity: 0.03;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M50 5L60 40L95 40L68 60L78 95L50 75L22 95L32 60L5 40L40 40Z" fill="%232563eb"/></svg>') repeat;
                background-size: 50px 50px;
                animation: patternMove 20s linear infinite;
            `;

            const style = document.createElement('style');
            style.textContent = `
                @keyframes patternMove {
                    0% { transform: translateX(0) translateY(0); }
                    100% { transform: translateX(50px) translateY(50px); }
                }
            `;
            document.head.appendChild(style);
            hero.appendChild(pattern);
        }

        // Initialize background pattern
        createBackgroundPattern();
    </script>
</body>

</html>
