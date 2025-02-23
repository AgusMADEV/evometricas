"""
Este programa monitoriza métricas del sistema, como uso de CPU, RAM, disco, velocidad de red y temperatura,
almacenando los datos cada hora en un archivo y generando gráficos de las métricas registradas.
"""
import matplotlib.pyplot as plt
import psutil
import time
import subprocess
import os
from datetime import datetime

# Definir las rutas de los archivos de datos y las carpetas para los gráficos
data_paths = {"hourly": "/var/www/html/evometricas/carga_hourly.txt"}
plot_folders = {"hourly": "/var/www/html/evometricas/img/hourly"}

# Crear las carpetas para los gráficos si no existen
for folder in plot_folders.values():
    os.makedirs(folder, exist_ok=True)

def trim_data(data, time_window_seconds):
    # Elimina datos fuera de la ventana de tiempo especificada
    now = datetime.now()
    return [entry for entry in data if (now - entry[0]).total_seconds() <= time_window_seconds]

def load_data(file_path):
    # Carga datos desde un archivo de texto y los convierte a formato adecuado
    try:
        with open(file_path, 'r') as f:
            return [(datetime.fromisoformat(row[0]), *map(float, row[1:])) for row in (line.strip().split(',') for line in f if line.strip())]
    except FileNotFoundError:
        return []

def save_data(file_path, data):
    # Guarda los datos en un archivo de texto en formato CSV
    with open(file_path, 'w') as f:
        for row in data:
            f.write(','.join(map(str, [row[0].isoformat()] + list(row[1:]))) + '\n')

def measure_metrics():
    # Obtiene métricas del sistema como uso de CPU, RAM, disco, red y temperatura
    carga_cpu = psutil.cpu_percent(interval=1)
    carga_ram = psutil.virtual_memory().percent
    uso_disco = psutil.disk_usage('/').percent
    data_inicio = psutil.net_io_counters()
    time.sleep(1)
    data_final = psutil.net_io_counters()
    descarga_mbps = (data_final.bytes_recv - data_inicio.bytes_recv) / (1024 * 1024)
    subida_mbps = (data_final.bytes_sent - data_inicio.bytes_sent) / (1024 * 1024)
    num_conexiones = len(psutil.net_connections())
    temperaturas = list(obtener_temperaturas())
    temperatura_promedio = sum(temperaturas) / len(temperaturas) if temperaturas else 0
    return datetime.now(), carga_cpu, carga_ram, uso_disco, descarga_mbps, subida_mbps, temperatura_promedio, num_conexiones

def obtener_temperaturas():
    # Obtiene temperaturas de la CPU si lm-sensors está disponible
    return []

# Cargar datos previos
data_buffers = {key: load_data(path) for key, path in data_paths.items()}

# Medir nuevas métricas y agregarlas a los datos
data_buffers["hourly"].append(measure_metrics())

# Eliminar datos más antiguos de una hora
data_buffers["hourly"] = trim_data(data_buffers["hourly"], 3600)

# Guardar los datos actualizados en los archivos
for key, path in data_paths.items():
    save_data(path, data_buffers[key])

def generate_plot(data, index, title, ylabel, save_path, ylim=None):
    # Genera gráficos de las métricas y los guarda como imágenes
    if not data:
        print(f"No data available for {title}. Skipping plot.")
        return
    timestamps = [row[0] for row in data]
    values = [row[index] for row in data]
    plt.figure(figsize=(10, 6))
    plt.plot(timestamps, values, label=title, marker='o')
    plt.grid(True)
    if ylim:
        plt.ylim(ylim)
    plt.title(title)
    plt.xlabel('Tiempo')
    plt.ylabel(ylabel)
    plt.legend()
    plt.tight_layout()
    plt.savefig(save_path)
    plt.close()

# Configuración de los gráficos a generar
plot_configs = [
    (1, 'Uso de CPU', 'Porcentaje de Uso', (0, 100)),
    (2, 'Uso de RAM', 'Porcentaje de Uso', (0, 100)),
    (3, 'Uso de Disco', 'Porcentaje de Uso', (0, 100)),
    (4, 'Descarga', 'Mbps', None),
    (5, 'Subida', 'Mbps', None),
    (6, 'Temperatura', 'Temperatura (°C)', None),
    (7, 'Conexiones Activas', 'Conexiones', None),
]

# Generar gráficos y guardarlos en las carpetas correspondientes
for index, title, ylabel, ylim in plot_configs:
    generate_plot(
        data_buffers["hourly"], index, f'{title} (Hourly)', ylabel,
        os.path.join(plot_folders["hourly"], f'{title.lower().replace(" ", "_")}_hourly.jpg'), ylim)

print("Métricas actualizadas y gráficas generadas correctamente.")