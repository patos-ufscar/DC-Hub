<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use PDO;

class AuthController
{
    private User $userModel;
    private RateLimiter $rateLimiter;

    public function __construct(private PDO $db)
    {
        $this->userModel = new User($db);
        $this->rateLimiter = new RateLimiter($db);
    }

    public function login(): void
    {
        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($email === '' || $senha === '') {
            Response::error('Preencha todos os campos.');
        }

        $bucket = RateLimiter::bucket('login', RateLimiter::clientIp(), strtolower($email));
        if (!$this->rateLimiter->attempt($bucket, 5, 900)) {
            Response::error('Muitas tentativas. Aguarde 15 minutos e tente novamente.', 429);
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($senha, $user['senha'])) {
            Response::error('Email ou senha incorretos.');
        }

        $this->rateLimiter->clear($bucket);

        $this->userModel->ensurePresencaUuid((int) $user['id']);
        $user = $this->userModel->findById((int) $user['id']);

        Session::regenerate();
        Session::setUser($user);

        Response::success('Login realizado com sucesso.', [
            'user' => [
                'id'            => $user['id'],
                'email'         => $user['email'],
                'nome_exibicao' => $user['nome_exibicao'],
                'nome_completo' => $user['nome_completo'],
                'role'          => $user['role'],
                'grupo_id'      => $user['grupo_id'],
                'grupo_nome'    => $user['grupo_nome'],
                'presenca_uuid' => $user['presenca_uuid'] ?? null,
            ],
        ]);
    }

    public function register(): void
    {
        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $email          = trim($_POST['email'] ?? '');
        $senha          = $_POST['senha'] ?? '';
        $senhaConfirm   = $_POST['senha_confirm'] ?? '';
        $nomeExibicao   = trim($_POST['nome_exibicao'] ?? '');

        if ($email === '' || $senha === '' || $nomeExibicao === '') {
            Response::error('Preencha todos os campos obrigatórios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Email inválido.');
        }

        $passwordError = $this->validatePassword($senha);
        if ($passwordError !== null) {
            Response::error($passwordError);
        }

        if ($senha !== $senhaConfirm) {
            Response::error('As senhas não conferem.');
        }

        if ($this->userModel->emailExists($email)) {
            Response::error('Não foi possível concluir o cadastro. Verifique os dados informados.');
        }

        $id = $this->userModel->create($email, $senha, $nomeExibicao);
        $user = $this->userModel->findById($id);

        Session::regenerate();
        Session::setUser($user);

        Response::success('Cadastro realizado com sucesso.', [
            'user' => [
                'id'            => $user['id'],
                'email'         => $user['email'],
                'nome_exibicao' => $user['nome_exibicao'],
                'nome_completo' => $user['nome_completo'],
                'role'          => $user['role'],
                'grupo_id'      => $user['grupo_id'],
                'grupo_nome'    => $user['grupo_nome'],
                'presenca_uuid' => $user['presenca_uuid'] ?? null,
            ],
        ]);
    }

    public function logout(): void
    {
        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        Session::destroy();
        Response::success('Logout realizado.');
    }

    public function updateProfile(): void
    {
        if (!Session::isLoggedIn()) {
            Response::error('Não autenticado.', 401);
        }

        if (!Csrf::validateRequest()) {
            Response::error('Token CSRF inválido.', 403);
        }

        $nomeCompleto = trim($_POST['nome_completo'] ?? '');
        $nomeExibicao = trim($_POST['nome_exibicao'] ?? '');

        if ($nomeCompleto === '') {
            Response::error('Nome completo é obrigatório.');
        }

        $userId = (int) Session::get('user_id');
        $this->userModel->updateProfile(
            $userId,
            $nomeCompleto,
            $nomeExibicao !== '' ? $nomeExibicao : null
        );

        Session::set('user_nome_completo', $nomeCompleto);
        if ($nomeExibicao !== '') {
            Session::set('user_nome_exibicao', $nomeExibicao);
        }

        Response::success('Perfil atualizado.', [
            'user' => Session::getUser(),
        ]);
    }

    private function validatePassword(string $senha): ?string
    {
        if (strlen($senha) < 8) {
            return 'A senha deve ter pelo menos 8 caracteres.';
        }

        if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
            return 'A senha deve conter letras e números.';
        }

        return null;
    }
}
