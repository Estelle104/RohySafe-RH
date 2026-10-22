<template>
  <div class="login-container">
    <div class="form-container">
      <p class="title">RohySafe</p>
      <form class="form">
        <input type="email" class="input" placeholder="Identifiant">
        <input type="password" class="input" placeholder="Mot de passe">
        <p class="page-link">
          <span class="page-link-label">Mot de passe oublie ?</span>
        </p>
        <button class="form-btn">Se connecter</button>
      </form>
    </div>
  </div>
</template>

<style scoped>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    html,
    body {
      margin: 0;
      min-height: 100vh;
    }

    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
      background: #f1f3f6;
      font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
        "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
    }

    .form-container {
      width: 100%;
      max-width: 350px;
      background-color: #fff;
      box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
      border-radius: 10px;
      padding: 30px;
    }

    .title {
      text-align: center;
      margin: 0 0 30px;
      font-size: 28px;
      font-weight: 800;
    }

    .form {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .input {
      width: 100%;
      border-radius: 20px;
      border: 1px solid #c0c0c0;
      padding: 12px 15px;
      outline: 0;
      transition: border-color 0.2s;
    }

    .input:focus {
      border-color: teal;
    }

    .page-link {
      margin: 0;
      text-align: end;
      color: #747474;
      text-decoration: underline;
      text-decoration-color: #747474;
    }

    .page-link-label {
      cursor: pointer;
      font-size: 12px;
      font-weight: 700;
    }

    .page-link-label:hover {
      color: #000;
    }

    .form-btn {
      padding: 12px 15px;
      font-family: inherit;
      border-radius: 20px;
      border: 0;
      outline: 0;
      background: teal;
      color: #fff;
      cursor: pointer;
      box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
    }

    .form-btn:hover {
      background: #006666;
    }

    .form-btn:active {
      box-shadow: none;
    }
</style>