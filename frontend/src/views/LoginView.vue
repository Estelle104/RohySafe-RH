<template>
  <div class="login-page">
    <div class="card login-card shadow-sm">
      <div class="card-body p-4">
        <h4 class="text-center mb-4">RohySafe RH</h4>
        <form>
          <div class="mb-3">
            <label class="form-label">Login</label>
            <input type="text" class="form-control" />
          </div>
          <div class="mb-3">
            <label class="form-label">Mot de passe</label>
            <input type="password" class="form-control" />
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="remember" />
            <label class="form-check-label" for="remember">Se souvenir de moi</label>
          </div>
          <button type="button" class="btn btn-primary w-100">Se connecter</button>
        </form>
      </div>
    </div>
  </div>
</template>
