# CC SEO 增強器

CC SEO 增強器是一個專注於 WordPress SEO 輔助功能的外掛，提供 metadata、Open Graph、Schema.org JSON-LD、追蹤碼、作者社群連結，以及動態 `robots.txt` 規則。

此外掛設計上配合 WordPress 內建 sitemap 功能使用，不會另外產生 `sitemap.xml` 實體檔。

## 功能特色

- 輸出 meta description、canonical URL、Open Graph 與 Twitter Card 標籤
- 輸出網站、組織/個人、麵包屑、文章、彙整頁與作者頁的 Schema.org JSON-LD
- 在作者個人檔案加入社群連結欄位
- 支援 Google Analytics、Google Tag Manager、Facebook Pixel 與站長工具驗證標籤
- 提供 Cookie 告知通知條
- 將自訂 `Disallow` 路徑加入 WordPress 動態 `robots.txt`
- 使用 WordPress 內建 `/wp-sitemap.xml`，不再產生自訂 sitemap
- 支援 i18n，多語系 text domain 為 `cc-seo-enhancer`

## 安裝方式

1. 將外掛資料夾 `cc-seo-enhancer` 上傳到 `/wp-content/plugins/`
2. 在 WordPress 後台啟用外掛
3. 前往 `設定 > CC SEO 增強器`
4. 設定 metadata、schema、追蹤碼與 robots.txt 選項

## 注意事項

- 外掛不會建立實體 `robots.txt` 或 `sitemap.xml` 檔案
- WordPress 會在 `/robots.txt` 提供動態 robots 輸出
- WordPress 內建 sitemap index 位於 `/wp-sitemap.xml`
- 若網站根目錄已有實體檔，伺服器設定可能會優先讀取實體檔
- 追蹤碼由各自開關控制；Cookie 通知條僅作為告知用途

## 更新紀錄

### 1.0.0

- 初始版本
- 加入 i18n 結構與繁體中文語言檔
- 使用 WordPress 內建 sitemap
- 使用 WordPress 動態 robots.txt 輸出
