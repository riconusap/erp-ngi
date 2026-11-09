export const focusFirstError = (fields?: Record<string, any>) => {
  setTimeout(() => {
    // Cari elemen input, textarea, atau select yang berada di dalam form-item dengan status error (.is-error)
    const errorElement = document.querySelector('.is-error input, .is-error textarea, .is-error .el-input__inner, .is-error .el-textarea__inner') as HTMLElement;
    
    if (errorElement) {
      errorElement.focus();
      // Opsional: gulir layar ke elemen tersebut jika di luar viewport
      errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, 100); // delay ditingkatkan sedikit agar Vue selesai memproses state DOM
};
