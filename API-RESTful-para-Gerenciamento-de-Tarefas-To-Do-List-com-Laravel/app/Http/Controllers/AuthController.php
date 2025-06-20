<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Autenticação
 *
 * Endpoints para gerenciamento de autenticação de usuários.
 */
class AuthController extends Controller
{
    /**
     * Registra um novo usuário.
     *
     * Cria um novo usuário e retorna um token de acesso.
     *
     * @bodyParam nome string required Nome do usuário (máx. 255 caracteres). Exemplo: João Silva
     * @bodyParam email string required E-mail único do usuário. Exemplo: joao@email.com
     * @bodyParam password string required Senha do usuário (mín. 6 caracteres). Exemplo: senha123
     * @response 201 {
     *   "success": true,
     *   "message": "Usuário registrado com sucesso.",
     *   "data": {
     *     "access_token": "1|abcdef1234567890",
     *     "token_type": "Bearer"
     *   }
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "nome": ["O campo nome é obrigatório."],
     *     "email": ["O campo e-mail deve ser um e-mail válido.", "O e-mail já está em uso."],
     *     "password": ["O campo senha deve ter pelo menos 6 caracteres."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao registrar usuário.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nome' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);

            $user = User::create([
                'name' => $request->nome,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Usuário registrado com sucesso.',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar usuário.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Autentica um usuário.
     *
     * Autentica o usuário com base nas credenciais fornecidas e retorna um token de acesso.
     *
     * @bodyParam email string required E-mail do usuário. Exemplo: joao@email.com
     * @bodyParam password string required Senha do usuário. Exemplo: senha123
     * @response 200 {
     *   "success": true,
     *   "message": "Login realizado com sucesso.",
     *   "data": {
     *     "access_token": "1|abcdef1234567890",
     *     "token_type": "Bearer"
     *   }
     * }
     * @responseError 422 {
     *   "success": false,
     *   "message": "Erro de validação.",
     *   "errors": {
     *     "email": ["O campo e-mail é obrigatório.", "O e-mail deve ser válido."],
     *     "password": ["O campo senha é obrigatório."]
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Credenciais inválidas.",
     *   "errors": {
     *     "email": ["As credenciais fornecidas estão incorretas."]
     *   }
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao realizar login.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciais inválidas.',
                    'errors' => [
                        'email' => ['As credenciais fornecidas estão incorretas.']
                    ]
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso.',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar login.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtém os dados do usuário autenticado.
     *
     * Retorna os detalhes do usuário atualmente autenticado.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Usuário autenticado recuperado com sucesso.",
     *   "data": {
     *     "id": 1,
     *     "name": "João Silva",
     *     "email": "joao@email.com",
     *     "created_at": "2025-05-28T17:00:00.000000Z",
     *     "updated_at": "2025-05-28T17:00:00.000000Z"
     *   }
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao recuperar usuário.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado.'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Usuário autenticado recuperado com sucesso.',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar usuário.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realiza o logout do usuário.
     *
     * Revoga todos os tokens do usuário autenticado.
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Logout realizado com sucesso."
     * }
     * @responseError 401 {
     *   "success": false,
     *   "message": "Não autorizado."
     * }
     * @responseError 500 {
     *   "success": false,
     *   "message": "Erro ao realizar logout.",
     *   "error": "Mensagem de erro detalhada."
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado.'
                ], 401);
            }

            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar logout.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
