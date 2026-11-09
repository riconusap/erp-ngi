export class ImageService {
  /**
   * Compress and convert an image File to WebP format.
   * @param file The original image file
   * @param quality Quality of the WebP image (0.0 to 1.0, default 0.8)
   * @param maxWidth Maximum width of the image (default 800)
   * @param maxHeight Maximum height of the image (default 800)
   * @returns A promise that resolves to the compressed WebP File
   */
  public static async compressToWebP(
    file: File,
    quality: number = 0.8,
    maxWidth: number = 800,
    maxHeight: number = 800
  ): Promise<File> {
    return new Promise((resolve, reject) => {
      const img = new Image();
      const reader = new FileReader();

      reader.onload = (e) => {
        if (e.target?.result) {
          img.src = e.target.result as string;
        }
      };
      
      reader.onerror = (error) => reject(error);

      img.onload = () => {
        const canvas = document.createElement('canvas');
        let width = img.width;
        let height = img.height;

        // Calculate aspect ratio to maintain proportions
        if (width > height) {
          if (width > maxWidth) {
            height = Math.round((height * maxWidth) / width);
            width = maxWidth;
          }
        } else {
          if (height > maxHeight) {
            width = Math.round((width * maxHeight) / height);
            height = maxHeight;
          }
        }

        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
          reject(new Error('Gagal memproses gambar pada browser'));
          return;
        }

        // Fill background with white in case image has transparency and target format doesn't support it 
        // (WebP supports transparency, but this is a good safety measure)
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(img, 0, 0, width, height);

        canvas.toBlob(
          (blob) => {
            if (!blob) {
              reject(new Error('Gagal mengkonversi gambar ke WebP'));
              return;
            }
            
            const newFileName = file.name.replace(/\.[^/.]+$/, "") + ".webp";
            const newFile = new File([blob], newFileName, {
              type: 'image/webp',
              lastModified: Date.now(),
            });
            
            resolve(newFile);
          },
          'image/webp',
          quality
        );
      };

      img.onerror = (error) => reject(error);

      reader.readAsDataURL(file);
    });
  }
}
