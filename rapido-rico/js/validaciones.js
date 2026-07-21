/**
 * js/validaciones.js
 * Validaciones de formularios reutilizables
 */

'use strict';

const Validar = {

  /** Valida que un campo no esté vacío */
  requerido(valor, campo) {
    if (!valor.trim()) {
      this.marcarError(campo, 'Este campo es obligatorio.');
      return false;
    }
    this.limpiarError(campo);
    return true;
  },

  /** Valida formato de correo electrónico */
  correo(valor, campo) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!re.test(valor.trim())) {
      this.marcarError(campo, 'Ingresa un correo electrónico válido.');
      return false;
    }
    this.limpiarError(campo);
    return true;
  },

  /** Valida longitud mínima de contraseña */
  contrasena(valor, campo, minLen = 8) {
    if (valor.length < minLen) {
      this.marcarError(campo, `La contraseña debe tener al menos ${minLen} caracteres.`);
      return false;
    }
    this.limpiarError(campo);
    return true;
  },

  /** Valida que dos contraseñas coincidan */
  coinciden(valor1, valor2, campo) {
    if (valor1 !== valor2) {
      this.marcarError(campo, 'Las contraseñas no coinciden.');
      return false;
    }
    this.limpiarError(campo);
    return true;
  },

  /** Valida que un número sea positivo */
  numero(valor, campo) {
    if (isNaN(valor) || parseFloat(valor) <= 0) {
      this.marcarError(campo, 'Ingresa un número válido mayor que 0.');
      return false;
    }
    this.limpiarError(campo);
    return true;
  },

  /** Muestra el mensaje de error debajo del campo */
  marcarError(campo, mensaje) {
    campo.classList.add('is-invalid');
    let feedback = campo.parentNode.querySelector('.invalid-feedback');
    if (!feedback) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';
      campo.parentNode.appendChild(feedback);
    }
    feedback.textContent = mensaje;
  },

  /** Limpia el error de un campo */
  limpiarError(campo) {
    campo.classList.remove('is-invalid');
    campo.classList.add('is-valid');
    const feedback = campo.parentNode.querySelector('.invalid-feedback');
    if (feedback) feedback.textContent = '';
  },

  /** Limpia todos los estados de validación del formulario */
  limpiarTodo(form) {
    form.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
      el.classList.remove('is-invalid', 'is-valid');
    });
  }
};

// ─── Validación Login ─────────────────────────────────
const formLogin = document.getElementById('formLogin');
if (formLogin) {
  formLogin.addEventListener('submit', function(e) {
    Validar.limpiarTodo(this);
    let ok = true;
    const correoInput = this.querySelector('[name="correo"]');
    const passInput   = this.querySelector('[name="password"]');

    if (!Validar.requerido(correoInput.value, correoInput)) ok = false;
    else if (!Validar.correo(correoInput.value, correoInput)) ok = false;
    if (!Validar.requerido(passInput.value, passInput)) ok = false;

    if (!ok) e.preventDefault();
  });
}

// ─── Validación Registro ──────────────────────────────
const formReg = document.getElementById('formReg');
if (formReg) {
  formReg.addEventListener('submit', function(e) {
    Validar.limpiarTodo(this);
    let ok = true;
    const nombre   = this.querySelector('[name="nombre"]');
    const correo   = this.querySelector('[name="correo"]');
    const pass1    = this.querySelector('[name="password"]');
    const pass2    = this.querySelector('[name="password2"]');

    if (!Validar.requerido(nombre.value, nombre))  ok = false;
    if (!Validar.requerido(correo.value, correo))  ok = false;
    else if (!Validar.correo(correo.value, correo)) ok = false;
    if (!Validar.contrasena(pass1.value, pass1))   ok = false;
    if (!Validar.coinciden(pass1.value, pass2.value, pass2)) ok = false;

    if (!ok) e.preventDefault();
  });
}

// ─── Validación Confirmar Pedido ──────────────────────
const formPedido = document.getElementById('formPedido');
if (formPedido) {
  formPedido.addEventListener('submit', function(e) {
    Validar.limpiarTodo(this);
    let ok = true;
    const dir = this.querySelector('[name="direccion"]');
    const tel = this.querySelector('[name="telefono"]');

    if (!Validar.requerido(dir.value, dir)) ok = false;
    if (!Validar.requerido(tel.value, tel)) ok = false;

    if (!ok) e.preventDefault();
  });
}
