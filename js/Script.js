document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. FILTROS DE BÚSQUEDA DINÁMICOS (index.php)
    // ==========================================
    const selectProvincia = document.getElementById('provincia');
    const selectComuna = document.getElementById('comuna');
    const selectSector = document.getElementById('sector');

    if (selectProvincia && selectComuna && selectSector) {
        // Diccionario completo de la 4ta Región
        const datosRegion = {
            elqui: {
                comunas: {
                    "La Serena": ["Centro", "Avenida del Mar", "Puerta del Mar", "San Joaquín", "Cerro Grande", "Las Compañías", "La Florida", "El Milagro", "Colina El Pino", "Caleta San Pedro", "El Romeral"],
                    "Coquimbo": ["Centro", "Peñuelas", "Sindempart", "La Herradura", "Tierras Blancas", "Punta Mira", "El Llano", "San Juan", "Guanaqueros", "Tongoy", "Totoralillo", "Pan de Azúcar"],
                    "Andacollo": ["Centro", "Casco Histórico", "Maitén"],
                    "La Higuera": ["Centro", "Caleta Hornos", "Chungungo", "Punta de Choros"],
                    "Paihuano": ["Centro", "Pisco Elqui", "Montegrande", "Horcón"],
                    "Vicuña": ["Centro", "Villaseca", "Peralillo", "Diaguitas", "Rivadavia", "El Tambo"]
                },
                
            },
            limari:  {
                    "Ovalle": ["Centro", "Parte Alta", "Población Limarí", "El Portal", "Tuquí", "Sotaquí", "Cerrillos de Tamaya", "Huamalata"],
                    "Combarbalá": ["Centro", "Quilitapia", "Cogotí"],
                    "Monte Patria": ["Centro", "Chañaral Alto", "El Palqui", "Huana", "Rapel"],
                    "Punitaqui": ["Centro", "Pueblo Viejo", "Mina Quiles"],
                    "Río Hurtado": ["Samo Alto", "Pichasca", "Serón", "Hurtado"]
                },
            choapa: {
                    "Illapel": ["Centro", "Parte Alta", "Villa San Rafael", "Asiento Viejo"],
                    "Canela": ["Canela Baja", "Canela Alta", "Huentelauquén", "Mincha"],
                    "Los Vilos": ["Centro", "Pichidangui", "Caimanes", "Quilimarí"],
                    "Salamanca": ["Centro", "Chillepín", "Batuco", "Tranquilla", "Cuncumén"]
                }
            };

        // Evento al cambiar Provincia
        selectProvincia.addEventListener('change', function() {
            const prov = this.value;
            selectComuna.innerHTML = '<option value="">Todas las comunas...</option>';
            selectSector.innerHTML = '<option value="">Todos los sectores...</option>';
            selectSector.disabled = true;

            if (prov && datosRegion[prov]) {
                selectComuna.disabled = false;
                const comunas = datosRegion[prov].comunas;
                for (let nombreComuna in comunas) {
                    let opt = document.createElement('option');
                    opt.value = nombreComuna;
                    opt.text = nombreComuna;
                    selectComuna.add(opt);
                }
            } else {
                selectComuna.disabled = true;
            }
        });

        // Evento al cambiar Comuna
        selectComuna.addEventListener('change', function() {
            const prov = selectProvincia.value;
            const com = this.value;
            selectSector.innerHTML = '<option value="">Todos los sectores...</option>';

            if (com && datosRegion[prov] && datosRegion[prov].comunas[com]) {
                selectSector.disabled = false;
                const sectores = datosRegion[prov].comunas[com];
                sectores.forEach(s => {
                    let opt = document.createElement('option');
                    opt.value = s;
                    opt.text = s;
                    selectSector.add(opt);
                });
            } else {
                selectSector.disabled = true;
            }
        });
    }

    // Nota: La sección 2 (Detalles Dinámicos) que usaba 'bdPropiedades' ha sido eliminada 
    // porque ahora detalles.php maneja esa lógica directamente desde el servidor.
});