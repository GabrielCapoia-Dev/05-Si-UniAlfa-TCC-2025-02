"# 🚌 GeoBus: Sistema de Gerenciamento do Transporte Escolar

Este repositório contém o código-fonte do **GeoBus**, um sistema desenvolvido como Trabalho de Conclusão de Curso (TCC) com o objetivo de modernizar e otimizar a gestão do transporte escolar no município de Umuarama.

## 💡 Contexto e Motivação

O projeto GeoBus nasceu da necessidade de superar os desafios impostos pela gestão manual dos dados de transporte escolar na cidade. A ausência de um controle automatizado e centralizado resultava em inconsistências, atrasos operacionais e potenciais riscos à segurança dos alunos.

O **objetivo principal** do GeoBus é proporcionar um controle eficiente, ágil e seguro das operações, facilitando o gerenciamento de informações e promovendo maior transparência e organização para todos os envolvidos.

## ✨ Funcionalidades Principais

O sistema GeoBus foi projetado para atender a diversos perfis de usuários (administradores escolares, gestores de transporte e responsáveis técnicos), oferecendo as seguintes funcionalidades essenciais:

*   **Gestão de Rotas e Otimização:** Cadastro, edição e visualização de rotas de transporte, permitindo a otimização do percurso para maior eficiência.
*   **Controle de Alunos Transportados:** Cadastro e gerenciamento centralizado dos dados dos alunos que utilizam o transporte escolar.
*   **Emissão de Carteirinhas:** Geração" e controle de carteirinhas de transporte para certificação e identificação segura dos alunos.
*   **Controle de Acesso:** Sistema de perfis que garante que c"ada usuário tenha acesso apenas às funcionalidades pertinentes às suas funções.

## 🛠️ Tecnologias Utilizadas

O GeoBus é uma aplicação robusta construída com tecnologias modernas e escaláveis:

| Categoria | Tecnologia | Descrição |
| :--- | :--- | :--- |
| **Backend/Framework** | **Laravel** | Utilizado para implementar a arquitetura **MVC (Model-View-Controller)**, garantindo uma separação clara entre a lógica de negócio e a interface. |
| **Painel Administrativo** | **Filament PHP** | Simplifica e agiliza a criação dos painéis de administração, permitindo a gestão rápida de Rotas, Frota, Usuários e Emissão de Carteirinhas. |
| **Geolocalização** | **Google Cloud Console** | Integração com APIs de geoposicionamento para funcionalidades de mapeamento, otimização de rotas e sincronização de dados geográficos. |

## 🚀 Como Iniciar (Setup Local)

Para configurar e rodar o projeto GeoBus em seu ambiente local, siga o guia detalhado de instalação e configuração.

**[Guia Completo de Instalação e Configuração (Notion)](https://www.notion.so/TCC-GeoBus-fbffab4dc0cc4f90b62ab3128a1e09f6)**

O guia inclui os passos necessários para:
1.  Clonar o repositório."
2.  Configurar o ambiente PHP e Composer.
3.  Configurar o banco de dados.
4.  Configurar as chaves de API do Google Cloud Console.
5.  Executar as migrações e seeds.

## 📄 Documentação Técnica

A documentação completa do TCC, incluindo User Stories detalhadas, Regras de Negócio, Testes de Aceitação, Modelagem de Dados e Arquitetura do Sistema, pode ser encontrada no diretório `Documentation/`.

## 🧑‍💻 Autores

*   Breno Labs
*   Gabriel Henrique
*   Gabriel Capoia

---
*Desenvolvido como Trabalho de Conclusão de Curso (TCC) para a faculdade UniAlfa.*
