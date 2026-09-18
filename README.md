# Magento 2 MageAI Extension
This Magento 2 extension integrates **OpenAI (GPT)**, **Anthropic (Claude)**, and **Google Gemini** to automatically generate high-quality short and long product descriptions, **AI-generated product images**, and **AI image editing of existing product photos** — based on product attributes like name, features, material, and more. It's a powerful tool to save time and improve content quality across your catalog.

❤️ The goal of this extension is to remain fully open-source and continuously expand by integrating every possible way to use AI with Magento 2. From writing content to helping customers, improving SEO, or automating tasks — the idea is to make Magento and AI work great together. I'm building it to be flexible and helpful for everyone, and I'd love for others to join in. If you're into Magento or AI, your ideas and contributions are always welcome. Let's create something awesome together!

## Features
- **Multi-provider AI support** — switch between OpenAI (GPT), Anthropic (Claude), and Google Gemini from a single config screen
- **AI product image generation** — generate a product image from a prompt (or a configurable default) right from the Images And Videos section, added straight to the gallery (OpenAI & Gemini)
- **AI product image editing** — pick any existing product image, describe the change in a prompt (or use a configurable default), preview the edited result side-by-side with the original, and replace it in the gallery on confirm (OpenAI & Gemini)
- **AI category image generation & editing** — the same two buttons on the category page: generate a category **banner** from a prompt (wide landscape framing with space for overlay text by default), or edit the current image and preview the result before replacing it (OpenAI & Gemini)
- **Global baseline prompt** — set brand voice, language, compliance and SEO rules once and have them automatically applied to every text generation (full, short, and custom prompts) across all providers
- Generate product descriptions using a **custom free-form prompt** for full control and flexibility
- Customize prompt templates using `{{ product.name }}` and `{{ product.attributes }}` variables
- Select **multiple product attributes** to base generation on (name, material, features, etc.)
- Configure **max tokens** and **temperature** separately for full and short descriptions
- Works on both **existing** and **unsaved (new) products**
- Supports OpenAI chat and completion endpoints (`gpt-6-astra`, `gpt-5.6-sol`, `gpt-5.6-terra`, `gpt-5.6-luna`, etc.)
- Supports Anthropic Messages API (`claude-fable-5-1`, `claude-opus-5`, `claude-sonnet-5`, `claude-haiku-4-5`, etc.)
- Supports Google Gemini API (`gemini-3.8-flash`, `gemini-3.5-flash`, `gemini-3.5-flash-lite`, etc.)
- Clean, valid HTML output ready to use in the WYSIWYG editor
- Compatible with Page Builder

## Supported AI Providers

| Provider | Models |
|---|---|
| **Google Gemini** *(default)* | `gemini-3.8-flash`, `gemini-3.7-flash`, `gemini-3.6-flash`, `gemini-3.5-flash`, `gemini-3.5-flash-lite`, `gemini-3.1-flash-lite`, `gemini-2.5-pro`, `gemini-2.5-flash`, `gemini-2.5-flash-lite` |
| **OpenAI (ChatGPT)** | `gpt-6-astra`, `gpt-5.6-sol`, `gpt-5.6-terra`, `gpt-5.6-luna`, `gpt-4o`, `gpt-4o-mini` |
| **Anthropic (Claude)** | `claude-fable-5-1`, `claude-opus-5`, `claude-sonnet-5`, `claude-haiku-4-5`, plus the 4.x legacy models |

## Usage

### Setup
1. Go to `Stores > Configuration > Mageprince > MageAI`
2. Choose your preferred AI **Provider** (Gemini, OpenAI, or Anthropic)
3. Enter the **API key** for the selected provider
4. Select the **model** and tune **max tokens** / **temperature** as needed
5. If you plan to use the image features, configure **Product Image Generation Configuration** and **Category Image Generation Configuration** — each group has its own prompts, image model, size and quality, so category banners can differ from product shots (category images default to a landscape banner)
6. Save the configuration

> All features below work on both **new (unsaved)** and **existing** products. Remember to save the product to persist generated content and images.

### Generate Descriptions
1. Open any product and locate the Short or Long Description editor
2. Click **"Generate with MageAI"**
3. The AI generates polished, HTML-ready content based on the product name and the attributes you selected in config
4. Review and save the product

### Custom Prompt (Descriptions)
Click **"Advanced Generate with MageAI"** to open the custom prompt modal and type any free-form instruction — the module skips attribute lookup and sends your prompt directly to the AI.

### Generate Product Image
Click **"Generate Image with MageAI"** next to the **Add Video** button in the Images And Videos section. Enter a prompt (or leave it empty to use the default configured prompt) and the generated image is added straight to the product gallery. Available with the **OpenAI** and **Gemini** providers.

### Edit Product Image
Click **"Edit Image with MageAI"** (next to the generate button) to open a popup listing the product's current images. Click **"Edit with MageAI"** under any image, describe the change in a prompt (or leave it empty to use the default configured modify prompt), and the AI returns an edited version shown side-by-side with the original. Click **Confirm & Replace** to swap the original image with the edited one — its base/role, position, and visibility are preserved, and the change is saved with the product. Available with the **OpenAI** and **Gemini** providers.

### Generate Category Image
Open any category and click **"Generate Image with MageAI"** next to the **Upload** / **Select from Gallery** buttons of the **Category Image** field. Enter a prompt (or leave it empty to use the default configured category prompt, which produces a wide banner-style image) and the generated image is set as the category image straight away. Save the category to persist it. Available with the **OpenAI** and **Gemini** providers.

### Edit Category Image
Click **"Edit Image with MageAI"** next to the generate button, describe the change in a prompt (or leave it empty to use the default configured category modify prompt), and the AI returns an edited version shown side-by-side with the original. Click **Confirm & Replace** to make it the category image. Available with the **OpenAI** and **Gemini** providers.

### Prompt Templates
Product description and product image prompts support two variables:
- `{{ product.name }}` — the product's name
- `{{ product.attributes }}` — a comma-separated `Label: Value` string from the attributes you selected in config (descriptions and image prompts have independent attribute selections)

Category image prompts support:
- `{{ category.name }}` — the category's name
- `{{ category.description }}` — the category description as plain text (tags stripped, shortened)

## How to Install
### Install via Composer

Run the following commands in your Magento 2 root folder:

```bash
composer require mageprince/magento2-mage-ai
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

## Contribution
Contributions are highly welcome and encouraged! 🙌

This project is a personal open-source effort to bring the power of AI to Magento 2. Whether you want to:
- Add new features
- Improve prompts or logic
- Fix bugs
- Help with translations or documentation
- Or just share feedback…

You're very welcome to join in.

**To contribute:**
1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add new feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Create a new Pull Request

## Screenshots

### Edit Product Image
<img width="1280" height="720" alt="mageprince-mageai-edit-image" src="https://github.com/user-attachments/assets/edbe7ee9-6817-4f4c-b27b-e1a7b8538c79" />

### Generate Product Image
<img width="1280" height="720" alt="mageprince-mageai-image-generati" src="https://github.com/user-attachments/assets/b87f6d38-39e4-4a5c-afa4-8996f5790349" />

### Generate Descriptions with Custom Prompt
![mageprince-mageai-final](https://github.com/user-attachments/assets/9fbdad1b-5d27-4dbb-a3ca-274f3036ba34)

### Generate Product Short Description
![short-description](https://github.com/user-attachments/assets/a88899fc-d70a-4138-9295-e79b4efd1308)

### Generate Product Long Description With Page Builder
![full-description-short](https://github.com/user-attachments/assets/54031479-5d68-4af9-9fc1-c24680e4a858)

