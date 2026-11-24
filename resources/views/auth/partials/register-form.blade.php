<form action="{{ route('register.post') }}" method="POST" class="space-y-4 text-left">
    @csrf
    <input type="hidden" name="auth_mode" value="register">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="reg-nombres" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Nombres *</label>
            <input type="text" id="reg-nombres" name="nombres" value="{{ old('nombres') }}" required
                   data-input-mode="letters"
                   pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s.'-]+"
                   title="Solo se permiten letras y espacios (sin números)."
                   placeholder="Ej. Mateo Sebastián"
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
        </div>

        <div>
            <label for="reg-apellidos" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Apellidos *</label>
            <input type="text" id="reg-apellidos" name="apellidos" value="{{ old('apellidos') }}" required
                   data-input-mode="letters"
                   pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s.'-]+"
                   title="Solo se permiten letras y espacios (sin números)."
                   placeholder="Ej. Salazar Vega"
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
        </div>

        <div>
            <label for="reg-dni" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">DNI / Documento (Opcional)</label>
            <input type="text" id="reg-dni" name="dni_codigo" value="{{ old('dni_codigo') }}"
                   inputmode="numeric"
                   data-input-mode="digits"
                   pattern="[0-9]{6,20}"
                   maxlength="20"
                   title="Solo números (entre 6 y 20 dígitos, sin letras)."
                   placeholder="Ej. 75849302 (opcional)"
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
        </div>

        <div>
            <label for="reg-telefono" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Teléfono / WhatsApp</label>
            <input type="tel" id="reg-telefono" name="telefono" value="{{ old('telefono') }}"
                   inputmode="numeric"
                   data-input-mode="digits"
                   pattern="[0-9]{7,15}"
                   maxlength="15"
                   title="Solo números (entre 7 y 15 dígitos, sin letras)."
                   placeholder="Ej. 987654321"
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
        </div>
    </div>

    <div>
        <label for="reg-email" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Correo Electrónico *</label>
        <input type="email" id="reg-email" name="email" value="{{ old('email') }}" required placeholder="tucorreo@gmail.com"
               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="reg-password" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Contraseña * (Mín. 6)</label>
            <input type="password" id="reg-password" name="password" required minlength="6" placeholder="••••••••"
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
        </div>

        <div>
            <label for="reg-password-confirmation" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Confirmar Contraseña *</label>
            <input type="password" id="reg-password-confirmation" name="password_confirmation" required minlength="6" placeholder="••••••••"
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
        </div>
    </div>

    <button type="submit"
            class="w-full bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black py-3.5 rounded-xl text-sm shadow-lg transition mt-2">
        🚀 Crear mi Cuenta y Entrar Ahora
    </button>
</form>
