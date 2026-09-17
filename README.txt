SISTEMA DE GESTÃO DE URGÊNCIA HOSPITALAR
Hospital de Mavalane - PHP + MySQL

REQUISITOS
- XAMPP com Apache e MySQL/MariaDB ativos
- Projeto colocado em C:\xampp\htdocs\PIPHP

INSTALAÇÃO
1. Copie a pasta PIPHP para C:\xampp\htdocs\
2. Abra phpMyAdmin ou MySQL Workbench.
3. Execute o conteúdo de database.sql.
4. Abra:
   http://localhost/PIPHP/index.php
5. Para criar o primeiro administrador, abra:
   http://localhost/PIPHP/criar_admin.php
   Email: admin@hospital.com
   Palavra-passe: 123456
6. Depois de criar o administrador, APAGUE criar_admin.php.
7. Entre em:
   http://localhost/PIPHP/login.php
8. O administrador pode criar enfermeiros e médicos.

FLUXO
Paciente:
index.php -> formulário -> processar_atendimento.php -> atendimento_criado.php

Profissional:
login.php -> autenticar.php -> dashboard conforme o tipo

Enfermeiro:
vê pacientes aguardando triagem -> atribui prioridade -> envia para médico

Médico:
vê pacientes aguardando médico -> inicia atendimento -> conclui atendimento

Admin:
vê indicadores -> gere profissionais -> consulta pacientes e atendimentos

IMPORTANTE
- O paciente NÃO escolhe a prioridade.
- A prioridade é atribuída pelo enfermeiro durante a triagem.
- As cores de prioridade:
  Emergente = vermelho
  Muito urgente = laranja
  Urgente = amarelo
  Pouco urgente = verde
  Não urgente = azul
