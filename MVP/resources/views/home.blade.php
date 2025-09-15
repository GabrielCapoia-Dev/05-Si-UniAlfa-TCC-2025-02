<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transporte Escolar Municipal - Umuarama - Educação com Segurança</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #f59e0b;
            --accent: #10b981;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--gray-200);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .header.scrolled {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gray-900);
        }
        
        .logo-subtitle {
            font-size: 0.8rem;
            color: var(--gray-600);
            font-weight: 500;
        }
        
        .login-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f7ff 0%, #e0f2fe 100%);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="%23e0f2fe" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
        }
        
        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .hero-text h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: var(--gray-900);
        }
        
        .hero-text .highlight {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-text p {
            font-size: 1.25rem;
            color: var(--gray-600);
            margin-bottom: 2.5rem;
            line-height: 1.7;
        }
        
        .hero-badges {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .badge {
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary);
            box-shadow: 0 2px 10px rgba(37, 99, 235, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-secondary {
            background: white;
            color: var(--gray-800);
            padding: 1rem 2rem;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.3);
        }
        
        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .hero-visual {
            position: relative;
        }
        
        .hero-image {
            width: 100%;
            max-width: 500px;
            height: 350px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 20px;
            position: relative;
            box-shadow: 0 20px 60px rgba(37, 99, 235, 0.2);
            animation: float 6s ease-in-out infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }
        
        .hero-image::before {
            content: '🚌';
            font-size: 8rem;
            position: absolute;
            opacity: 0.9;
        }
        
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .floating-element {
            position: absolute;
            background: white;
            border-radius: 10px;
            padding: 0.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            animation: floatElements 8s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            top: 10%;
            right: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            bottom: 20%;
            left: 10%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            top: 60%;
            right: 20%;
            animation-delay: 4s;
        }
        
        @keyframes floatElements {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        
        /* Stats Section */
        .stats {
            padding: 6rem 0;
            background: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }
        
        .stat-card {
            text-align: center;
            padding: 2.5rem 2rem;
            background: var(--gray-50);
            border-radius: 20px;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            background: white;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: var(--gray-600);
            font-weight: 600;
        }
        
        /* Services Section */
        .services {
            padding: 6rem 0;
            background: linear-gradient(135deg, var(--gray-50), white);
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .service-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }
        
        .service-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .service-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .service-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gray-900);
        }
        
        .service-description {
            color: var(--gray-600);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }
        
        .service-features {
            list-style: none;
        }
        
        .service-features li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-700);
        }
        
        .service-features li::before {
            content: '✓';
            color: var(--accent);
            font-weight: bold;
        }
        
        /* Schools Section */
        .schools {
            padding: 6rem 0;
            background: white;
        }
        
        .schools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .school-card {
            background: linear-gradient(135deg, var(--gray-50), white);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
        }
        
        .school-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        }
        
        .school-name {
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .school-info {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .school-students {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        /* CTA Section */
        .cta {
            padding: 6rem 0;
            background: linear-gradient(135deg, var(--gray-900), var(--gray-800));
            color: white;
            text-align: center;
            position: relative;
        }
        
        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.05"/></svg>') repeat;
        }
        
        .cta-content {
            position: relative;
            z-index: 2;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .cta p {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta .btn-primary {
            background: linear-gradient(135deg, var(--secondary), #f59e0b);
        }
        
        .cta .btn-primary:hover {
            box-shadow: 0 15px 35px rgba(245, 158, 11, 0.3);
        }
        
        /* Footer */
        .footer {
            background: var(--gray-900);
            color: var(--gray-200);
            padding: 4rem 0 2rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            color: white;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .footer-section p, 
        .footer-section a {
            color: var(--gray-200);
            text-decoration: none;
            line-height: 1.8;
            transition: color 0.3s ease;
        }
        
        .footer-section a:hover {
            color: var(--primary-light);
        }
        
        .footer-bottom {
            border-top: 1px solid var(--gray-800);
            padding-top: 2rem;
            text-align: center;
            color: var(--gray-600);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 3rem;
                text-align: center;
            }
            
            .hero-text h1 {
                font-size: 2.5rem;
            }
            
            .nav {
                padding: 1rem;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .logo-text {
                display: none;
            }
            
            .hero-image {
                height: 250px;
            }
            
            .hero-image::before {
                font-size: 4rem;
            }
            
            .services-grid,
            .schools-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Scroll animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
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
                    <p>Sistema integrado de gestão do transporte escolar municipal, garantindo segurança, eficiência e qualidade no atendimento aos nossos estudantes.</p>
                    
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
                <p class="section-subtitle">Oferecemos um sistema completo de transporte escolar com foco na segurança, pontualidade e bem-estar dos nossos estudantes.</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card fade-in">
                    <div class="service-header">
                        <div class="service-icon">🚌</div>
                        <h3 class="service-title">Transporte Regular</h3>
                    </div>
                    <p class="service-description">Atendimento diário aos estudantes da rede municipal e estadual, com rotas otimizadas e horários rigorosamente cumpridos.</p>
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
                    <p class="service-description">Plataforma digital completa para controle e monitoramento de toda a operação do transporte escolar municipal.</p>
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
                    <p class="service-description">Atendimento especializado para estudantes com necessidades especiais e atividades extracurriculares.</p>
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
                <p class="section-subtitle">Atendemos todas as principais escolas municipais, estaduais e instituições de ensino da região de Umuarama</p>
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
                <p>Acesse nossa plataforma administrativa para gerenciar rotas, monitorar veículos, controlar estudantes e gerar relatórios completos do sistema de transporte escolar municipal.</p>
                <div class="cta-buttons">
                    <a href="/admin/login" class="btn-primary">
                        <span>🔐</span>
                        Acessar Sistema
                    </a>
                </div>
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
            anchor.addEventListener('click', function (e) {
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
                }, { threshold: 0.5 });
                
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