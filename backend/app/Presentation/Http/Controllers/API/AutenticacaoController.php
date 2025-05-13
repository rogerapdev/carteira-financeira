<?php

namespace App\Presentation\Http\Controllers\API;

use App\Application\DTOs\UsuarioDTO;
use App\Application\Interfaces\UsuarioServiceInterface;
use App\Http\Controllers\Controller;
use App\Presentation\Http\Requests\LoginRequest;
use App\Presentation\Http\Requests\RegisterRequest;
use App\Presentation\Serializers\SimpleArraySerializer;
use App\Presentation\Transformers\UsuarioTransformer;
use App\Presentation\Transformers\ContaTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;

/**
 * @OA\Tag(
 *     name="Autenticação",
 *     description="Endpoints para gerenciamento de autenticação de usuários"
 * )
 */
class AutenticacaoController extends Controller
{
    /**
     * @param UsuarioServiceInterface $servicoUsuario
     * @param Manager $fractal
     */
    public function __construct(
        private UsuarioServiceInterface $servicoUsuario,
        private Manager $fractal
    ) {
        $this->fractal->setSerializer(new DataArraySerializer());
    }

    /**
     * Registra um novo usuário.
     *
     * @OA\Post(
     *     path="/api/cadastrar",
     *     tags={"Autenticação"},
     *     summary="Registra um novo usuário no sistema",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "phone", "document"},
     *             @OA\Property(property="name", type="string", example="João Silva"),
     *             @OA\Property(property="email", type="string", format="email", example="joao@email.com"),
     *             @OA\Property(property="password", type="string", format="password", example="senha123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="senha123"),
     *             @OA\Property(property="phone", type="string", example="11999999999"),
     *             @OA\Property(property="document", type="string", example="12345678901")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="João Silva"),
     *                 @OA\Property(property="email", type="string", example="joao@email.com"),
     *                 @OA\Property(property="phone", type="string", example="11999999999"),
     *                 @OA\Property(property="document", type="string", example="12345678901")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensagem", type="string", example="O email já está em uso.")
     *         )
     *     )
     * )
     *
     * @param RegisterRequest $requisicao
     * @return JsonResponse
     */
    public function cadastrar(RegisterRequest $requisicao): JsonResponse
    {
        try {
            $usuarioDTO = UsuarioDTO::deArray($requisicao->validated());
            $usuario = $this->servicoUsuario->criarUsuario($usuarioDTO);

            $recurso = new Item($usuario, new UsuarioTransformer(), 'users');
            $dados = $this->fractal->createData($recurso)->toArray();

            return response()->json($dados, 201);
        } catch (\Exception $e) {
            return response()->json([
                'mensagem' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Autentica o usuário e cria token.
     *
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Autenticação"},
     *     summary="Autentica um usuário e retorna o token de acesso",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="joao@email.com"),
     *             @OA\Property(property="password", type="string", format="password", example="senha123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="João Silva"),
     *                 @OA\Property(property="email", type="string", example="joao@email.com"),
     *                 @OA\Property(property="phone", type="string", example="11999999999"),
     *                 @OA\Property(property="document", type="string", example="12345678901"),
     *                 @OA\Property(property="token", type="string", example="1|abcdef123456...")
     *             )    
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensagem", type="string", example="Credenciais inválidas")
     *         )
     *     )
     * )
     *
     * @param LoginRequest $requisicao
     * @return JsonResponse
     */
    public function login(LoginRequest $requisicao): JsonResponse
    {
        $credenciais = $requisicao->only(['email', 'password']);

        if (!Auth::attempt($credenciais)) {
            return response()->json([
                'mensagem' => 'Credenciais inválidas'
            ], 401);
        }

        $usuario = Auth::user();
        $token = $usuario->createToken('api-token')->plainTextToken;

        $recurso = new Item($usuario, new UsuarioTransformer(), 'users');
        $dados = $this->fractal->createData($recurso)->toArray();
        $dados['data']['token'] = $token;

        return response()->json($dados);
    }

    /**
     * Obtém o usuário autenticado.
     *
     * @OA\Get(
     *     path="/api/perfil",
     *     tags={"Autenticação"},
     *     summary="Retorna os dados do usuário autenticado, incluindo os dados da conta",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dados do usuário retornados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="123e4567-e89b-12d3-a456-426614174000"),
     *                 @OA\Property(property="name", type="string", example="João Silva"),
     *                 @OA\Property(property="email", type="string", example="joao@email.com"),
     *                 @OA\Property(property="phone", type="string", example="11999999999"),
     *                 @OA\Property(property="document", type="string", example="12345678901"),
     *                 @OA\Property(property="conta", type="object",
     *                     description="Dados da conta vinculada ao usuário",
     *                     @OA\Property(property="id", type="string", description="ID público da conta", example="c1a2b3c4-d5e6-7890-abcd-1234567890ef"),
     *                     @OA\Property(property="user_id", type="string", description="ID público do usuário", example="123e4567-e89b-12d3-a456-426614174000"),
     *                     @OA\Property(property="balance", type="number", format="float", description="Saldo atual da conta", example=1000.50),
     *                     @OA\Property(property="status", type="string", description="Status atual da conta", example="ativa"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação", example="2024-05-01T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", description="Data de última atualização", example="2024-05-10T15:30:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function perfil(): JsonResponse
    {
        $usuario = Auth::user();
        $usuario->load('conta');

        // Approach 2: Manual formatting to match the expected format
        // Get user data without the conta relationship
        $recurso = new Item($usuario, new UsuarioTransformer(), 'users');
        $userData = $this->fractal->createData($recurso)->toArray()['data'];
        
        // If the user has a conta, get its data separately
        if ($usuario->conta) {
            $contaRecurso = new Item($usuario->conta, new ContaTransformer(), 'contas');
            $contaData = $this->fractal->createData($contaRecurso)->toArray()['data'];
            
            // Add the conta data directly to the user data without the 'data' wrapper
            $userData['conta'] = $contaData;
        } else {
            $userData['conta'] = null;
        }
        
        // Return the formatted response
        return response()->json(['data' => $userData]);
    }

    /**
     * Desconecta o usuário (revoga o token).
     *
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Autenticação"},
     *     summary="Revoga o token de acesso do usuário atual",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensagem", type="string", example="Logout realizado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()->delete();

        return response()->json([
            'mensagem' => 'Desconectado com sucesso'
        ]);
    }
}