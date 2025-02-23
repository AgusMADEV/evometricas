"""
Este script ejecuta en bucle el script 'evometricas.py' usando subprocess,
reiniciándolo cada segundo. Se detiene con Ctrl+C (KeyboardInterrupt)
y maneja errores en la ejecución del script monitoreado.
"""
import subprocess
import time

def main():
    script_path = "evometricas.py"  # Ruta del script a ejecutar
    try:
        while True:
            subprocess.run(["python3", script_path], check=True)  # Ejecuta el script con Python3
            time.sleep(1)  # Pausa de 1 segundo entre ejecuciones
    except KeyboardInterrupt:
        print("Loop detenido por el usuario.")  # Mensaje cuando se interrumpe con Ctrl+C
    except subprocess.CalledProcessError as e:
        print(f"Error en la ejecución del script: {e}")  # Captura errores en la ejecución del script

if __name__ == "__main__":
    main()
