/**
 * Globals provided by WordPress (wp_localize_script) and third-party scripts.
 */

export {};

declare global {
  /** Localized in inc/assets.php only on single posts with Infinite Article enabled. */
  interface ArtpressInfiniteSettings {
    limit: number | string;
  }

  /** AdSense queue: a plain array until adsbygoogle.js replaces it with a live object. */
  interface AdsByGoogleQueue {
    push(...items: unknown[]): unknown;
    length?: number;
  }

  /** Advanced Ads consent handling (only present when that plugin is active). */
  interface AdvancedAdsPrivacy {
    state?: string;
    decode_ad?: (script: HTMLScriptElement) => void;
  }

  interface Window {
    artpressInfinite?: ArtpressInfiniteSettings;
    adsbygoogle?: AdsByGoogleQueue;
    advads?: { privacy?: AdvancedAdsPrivacy };
  }

  interface DocumentEventMap {
    'artpress:article-loaded': CustomEvent<ArticleLoadedDetail>;
  }

  interface ArticleLoadedDetail {
    article: HTMLElement;
    postId: number;
  }
}
