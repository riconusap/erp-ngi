import type { App } from "vue";
import type { AxiosInstance, AxiosResponse, AxiosRequestConfig } from "axios";
import axios from "axios";
import VueAxios from "vue-axios";
import JwtService from "@/core/services/JwtService";

const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_APP_API_URL || "",
});

// Inject Bearer token secara otomatis pada setiap HTTP request
apiClient.interceptors.request.use(
  (config) => {
    const token = JwtService.getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

/**
 * @description service to call HTTP request via Axios
 */
class ApiService {
  public static vueInstance: App;

  public static init(app: App<Element>) {
    ApiService.vueInstance = app;
    ApiService.vueInstance.use(VueAxios, apiClient as any);

    // Response interceptor dipasang setelah Pinia aktif agar store aman dipanggil
    apiClient.interceptors.response.use(
      (response) => response,
      async (error) => {
        if (error.response?.status === 401) {
          // Prevent infinite loop if logout itself returns 401
          if (error.config?.url === 'logout' || error.config?.url?.endsWith('/logout')) {
            return Promise.reject(error);
          }

          const { useAuthStore } = await import("@/store/auth");
          const { default: router } = await import("@/router");

          const authStore = useAuthStore();
          await authStore.logout();

          if (router.currentRoute.value.name !== "Login") {
            router.push({ name: "Login" });
          }
        }

        return Promise.reject(error);
      }
    );
  }

  public static setHeader(): void {
    const token = JwtService.getToken();
    if (token) {
      apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
  }

  public static query(resource: string, params: AxiosRequestConfig): Promise<AxiosResponse> {
    return apiClient.get(resource, params);
  }

  public static get(resource: string, config: AxiosRequestConfig = {}): Promise<AxiosResponse> {
    return apiClient.get(`${resource}`, config);
  }

  public static post(resource: string, params?: any, config: AxiosRequestConfig = {}): Promise<AxiosResponse> {
    return apiClient.post(`${resource}`, params, config);
  }

  public static update(
    resource: string,
    slug: string,
    params: any
  ): Promise<AxiosResponse> {
    return apiClient.put(`${resource}/${slug}`, params);
  }

  public static patch(resource: string, params: any, config: AxiosRequestConfig = {}): Promise<AxiosResponse> {
    return apiClient.patch(`${resource}`, params, config);
  }

  public static put(resource: string, params?: any, config: AxiosRequestConfig = {}): Promise<AxiosResponse> {
    return apiClient.put(`${resource}`, params, config);
  }

  public static delete(resource: string, config: AxiosRequestConfig = {}): Promise<AxiosResponse> {
    return apiClient.delete(resource, config);
  }
}

export default ApiService;