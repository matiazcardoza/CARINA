import {
  Component,
  ElementRef,
  viewChild, // <-- Nueva forma de @ViewChild
  input,     // <-- Nueva forma de @Input
  output,    // <-- Nueva forma de @Output
  computed,  // <-- Para valores derivados
  effect,
  inject,
  signal
} from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common'; //

// @Component({
//   selector: 'app-upload-file',
//   imports: [CommonModule, ReactiveFormsModule],
//   templateUrl: './upload-file.html',
//   styleUrl: './upload-file.css'
// })
// export class UploadFile {
// // Importaciones clave actualizadas
// import {
//   Component,
//   ElementRef,
//   viewChild, // <-- Nueva forma de @ViewChild
//   input,     // <-- Nueva forma de @Input
//   output,    // <-- Nueva forma de @Output
//   computed,  // <-- Para valores derivados
//   effect,
//   inject
// } from '@angular/core';
// import { HttpClient } from '@angular/common/http';
// import { CommonModule } from '@angular/common'; // CommonModule ya no es necesario para @if

@Component({
  selector: 'app-upload-file',
  standalone: true,
  imports: [], // Ya no se necesita CommonModule para control de flujo
  templateUrl: './upload-file.html',
  styleUrl: './upload-file.css'
})
export class UploadFile {
  // --- INYECCIÓN DE DEPENDENCIAS MODERNA ---
  private http = inject(HttpClient);
  
  // --- ENTRADAS (INPUTS) Y SALIDAS (OUTPUTS) MODERNAS ---
  uploadUrl = input<string>('/api/subir-foto'); // Define valor por defecto directamente
  maxFileSize = input<number>(5 * 1024 * 1024); // 5 MB
  additionalData = input<Record<string, string | Blob>>({});

  uploadComplete = output<any>(); // Emite la respuesta del servidor
  uploadError = output<any>();    // Emite el error

  // --- REFERENCIAS A VISTAS (VIEW REFERENCES) MODERNAS ---
  private fileInputRef = viewChild.required<ElementRef<HTMLInputElement>>('fileInput');
  private videoRef = viewChild.required<ElementRef<HTMLVideoElement>>('video');
  private canvasRef = viewChild.required<ElementRef<HTMLCanvasElement>>('canvas');

  // --- SIGNALS PARA EL ESTADO ---
  selectedFile = signal<File | null>(null);
  isUploading = signal<boolean>(false);
  errorMsg = signal<string | null>(null);
  cameraActive = signal<boolean>(false);
  isDragging = signal<boolean>(false); // Para el estado de drag & drop

  // --- SEÑALES DERIVADAS (COMPUTED SIGNALS) ---
  // Calcula la URL de vista previa solo cuando el archivo seleccionado cambia
  previewUrl = computed<string | null>(() => {
    const file = this.selectedFile();
    if (file) {
      return URL.createObjectURL(file);
    }
    return null;
  });

  private cameraStream: MediaStream | null = null;

  constructor() {
    // --- EFFECT PARA MANEJAR EFECTOS SECUNDARIOS (COMO LIMPIEZA) ---
    effect((onCleanup) => {
      const url = this.previewUrl(); // "Escucha" los cambios en previewUrl
      
      // onCleanup se ejecuta antes de la siguiente ejecución del effect o al destruir
      onCleanup(() => {
        if (url) {
          URL.revokeObjectURL(url); // Libera la memoria del Blob URL anterior
        }
      });
    });
  }

  // --- MANEJO DE ARCHIVOS ---
  onFileSelected(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) this.processFile(file);
  }

  onDrop(event: DragEvent): void {
    event.preventDefault();
    this.isDragging.set(false);
    const file = event.dataTransfer?.files?.[0];
    if (file) this.processFile(file);
  }

  onDragOver(event: DragEvent): void {
    event.preventDefault();
    event.dataTransfer!.dropEffect = 'copy';
    this.isDragging.set(true);
  }
  
  onDragLeave(): void {
    this.isDragging.set(false);
  }

  private processFile(file: File): void {
    this.errorMsg.set(null);
    if (!file.type.startsWith('image/')) {
      this.errorMsg.set('El archivo debe ser una imagen.');
      return;
    }
    if (file.size > this.maxFileSize()) { // <-- se llama como una función
      this.errorMsg.set(`La imagen supera el tamaño máximo de ${(this.maxFileSize() / (1024 * 1024)).toFixed(2)} MB.`);
      return;
    }
    this.selectedFile.set(file);
  }

  triggerFileDialog(): void {
    this.fileInputRef().nativeElement.click();
  }

  // --- MANEJO DE CÁMARA (con async/await, que ya es moderno) ---
  async openCamera(): Promise<void> {
    if (!navigator.mediaDevices?.getUserMedia) {
      this.errorMsg.set('La cámara no es soportada.');
      return;
    }
    try {
      this.cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
      const videoEl = this.videoRef().nativeElement;
      videoEl.srcObject = this.cameraStream;
      await videoEl.play();
      this.cameraActive.set(true);
      this.errorMsg.set(null);
    } catch (err) {
      console.error('Error al acceder a la cámara:', err);
      this.errorMsg.set('No se pudo acceder a la cámara.');
    }
  }

  capturePhoto(): void {
    if (!this.cameraStream) return;
    const video = this.videoRef().nativeElement;
    const canvas = this.canvasRef().nativeElement;
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d')?.drawImage(video, 0, 0);
    
    canvas.toBlob(blob => {
      if (blob) {
        const file = new File([blob], 'foto_capturada.png', { type: 'image/png' });
        this.processFile(file);
        this.closeCamera();
      }
    }, 'image/png');
  }

  closeCamera(): void {
    this.cameraStream?.getTracks().forEach(t => t.stop());
    this.cameraStream = null;
    this.cameraActive.set(false);
  }

  // --- LÓGICA DE SUBIDA (prácticamente igual, ya es moderna) ---
  uploadImage(): void {
    const file = this.selectedFile();
    if (!file || this.isUploading()) return;

    this.isUploading.set(true);
    const formData = new FormData();
    formData.append('foto', file, file.name);
    
    Object.entries(this.additionalData()).forEach(([key, value]) => {
      formData.append(key, value);
    });

    this.http.post(this.uploadUrl(), formData).subscribe({
      next: res => {
        this.uploadComplete.emit(res);
        this.selectedFile.set(null); // Resetea
      },
      error: err => {
        console.error('Error en la subida:', err);
        this.uploadError.emit(err);
      },
      complete: () => this.isUploading.set(false)
    });
  }

  // ngOnDestroy ya no es necesario si effect() maneja la limpieza

}
