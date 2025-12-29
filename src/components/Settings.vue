
<template>
  <div class="pt-2 pr-4">
    <div
      v-if="toast.visible"
      :class="['toast', toast.position]"
      :style="{ zIndex: 9999 }"
    >
      <div :class="['alert', toast.type, 'text-white']">
        <span>{{ toast.message }}</span>
      </div>
    </div>

    <remove-modal
      modal-id="modal_settings_remove"
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="handleDeletion(deleteType, selectedId)"
      @cancel="showRemoveModal = false"
    />

    <Card topMargin="mt-8">
      <div class="flex justify-between items-center">
        <h2 class="card-title">{{ translations.settings }}</h2>
      </div>
      <div class="divider mt-2"></div>

      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
      >
        <!-- Tabs (1/3 width) -->
        <div class="tabs tabs-vertical tabs-boxed col-span-1">
          <a
            :class="tabClass(1)"
            @click="selectTab(1)"
            class="justify-start w-full"
          >
            <i class="fas fa-home mr-2"></i> {{ translations.general_settings }}
          </a>

          <a
            :class="tabClass(2)"
            @click="selectTab(2)"
            class="justify-start w-full"
          >
            <i class="fas fa-tools mr-2"></i>
            {{ translations.system_settings }}
          </a>
          <a
            :class="tabClass(3)"
            @click="selectTab(3)"
            class="justify-start w-full"
          >
            <i class="fas fa-newspaper mr-2"></i>
            {{ translations.articles_settings }}
          </a>
          <a
            :class="tabClass(4)"
            @click="selectTab(4)"
            class="justify-start w-full"
          >
            <i class="fas fa-file-invoice-dollar mr-2"></i>
            {{ translations.invoices_settings }}
          </a>
          <a
            :class="tabClass(5)"
            @click="selectTab(5)"
            class="justify-start w-full"
          >
            <i class="fas fa-undo mr-2"></i>
            {{ translations.credits_settings }}
          </a>
          <a
            :class="tabClass(6)"
            @click="selectTab(6)"
            class="justify-start w-full"
          >
            <i class="far fa-question-circle mr-2"></i>
            {{ translations.quotes_settings }}
          </a>
          <a
            :class="tabClass(7)"
            @click="selectTab(7)"
            class="justify-start w-full"
          >
            <i class="fas fa-dollar-sign mr-2"></i>
            {{ translations.currency_vat_settings }}
          </a>
          <a
            :class="tabClass(8)"
            @click="selectTab(8)"
            class="justify-start w-full"
          >
            <i class="fas fa-money-check-alt mr-2"></i>
            {{ translations.payments_settings }}
          </a>
          <a
            :class="tabClass(9)"
            @click="selectTab(9)"
            class="justify-start w-full"
          >
            <i class="fas fa-shopping-basket mr-2"></i>
            {{ translations.expenses_settings }}
          </a>
          <a
            v-if="form.easy_compta_planning_addon_active == 1"
            :class="tabClass(10)"
            @click="selectTab(10)"
            class="justify-start w-full"
          >
            <i class="fas fa-calendar-alt mr-2"></i>
            {{ translations.planning_settings }}
          </a>
          <a
            v-if="form.easy_compta_email_addon_active == 1"
            :class="tabClass(11)"
            @click="selectTab(11)"
            class="justify-start w-full"
          >
            <i class="far fa-envelope mr-2"></i>
            {{ translations.email_settings }}
          </a>
          <a
            v-if="form.easy_compta_user_addon_active == 1"
            :class="tabClass(12)"
            @click="selectTab(12)"
            class="justify-start w-full"
          >
            <i class="fas fa-user mr-2"></i>
            {{ translations.users_settings }}
          </a>
          <a
            v-if="form.easy_compta_payment_addon_active == 1"
            :class="tabClass(13)"
            @click="selectTab(13)"
            class="justify-start w-full"
          >
            <i class="far fa-credit-card mr-2"></i>
            {{ translations.stripe_settings }}
          </a>
          <a
            v-if="form.easy_compta_stats_addon_active == 1"
            :class="tabClass(14)"
            @click="selectTab(14)"
            class="justify-start w-full"
          >
            <i class="far fa-chart-bar mr-2"></i>
            {{ translations.stats_settings }}
          </a>
          <a
            v-if="form.easy_compta_qrcode_addon_active == 1"
            :class="tabClass(15)"
            @click="selectTab(15)"
            class="justify-start w-full"
          >
            <i class="fas fa-qrcode mr-2"></i>
            {{ translations.qrcode_settings }}
          </a>
          <a
            :class="tabClass(16)"
            @click="selectTab(16)"
            class="justify-start w-full"
          >
            <i class="far fa-id-badge mr-2"></i>
            {{ translations.validation_license }}
          </a>
        </div>

        <div
          class="col-span-3 p-4 bg-base-300 rounded-lg shadow-md content-tabs"
        >
          <div
            v-if="loading"
            class="fixed top-0 left-0 w-full h-full flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
          >
            <span
              class="loading loading-spinner text-primary loading-lg"
            ></span>
          </div>
          <div v-if="selectedTab === 1">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.general_settings }}
            </h2>
            <form @submit.prevent="handleSubmit">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="company-code">{{
                    translations.company_code
                  }}</label>
                  <input
                    type="text"
                    id="company-code"
                    v-model="form.company_code"
                    class="ecwp-input input input-bordered"
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="tax-number">{{
                    translations.tax_number
                  }}</label>
                  <input
                    type="text"
                    id="tax-number"
                    v-model="form.tax_number"
                    class="ecwp-input input input-bordered"
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="company-name">{{
                    translations.company_name
                  }}</label>
                  <input
                    type="text"
                    id="company-name"
                    v-model="form.company_name"
                    class="ecwp-input input input-bordered"
                    required
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="company-address">{{
                    translations.address
                  }}</label>
                  <input
                    type="text"
                    id="company-address"
                    v-model="form.company_address"
                    class="ecwp-input input input-bordered"
                    required
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="postal-code">{{
                    translations.postal_code
                  }}</label>
                  <input
                    type="text"
                    id="postal-code"
                    v-model="form.postal_code"
                    class="ecwp-input input input-bordered"
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="city">{{
                    translations.city
                  }}</label>
                  <input
                    type="text"
                    id="city"
                    v-model="form.city"
                    class="ecwp-input input input-bordered"
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="country">{{
                    translations.country
                  }}</label>
                  <input
                    type="text"
                    id="country"
                    v-model="form.country"
                    class="ecwp-input input input-bordered"
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="company-email">{{
                    translations.email
                  }}</label>
                  <input
                    type="email"
                    id="company-email"
                    v-model="form.company_email"
                    class="ecwp-input input input-bordered"
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="company-phone">{{
                    translations.phone
                  }}</label>
                  <input
                    type="tel"
                    id="company-phone"
                    v-model="form.company_phone"
                    class="ecwp-input input input-bordered"
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="mobile-phone">{{
                    translations.mobile
                  }}</label>
                  <input
                    type="tel"
                    id="mobile-phone"
                    v-model="form.mobile_phone"
                    class="ecwp-input input input-bordered"
                  />
                </div>
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="fax">{{
                    translations.fax
                  }}</label>
                  <input
                    type="tel"
                    id="fax"
                    v-model="form.fax"
                    class="ecwp-input input input-bordered"
                  />
                </div>
              </div>
              <div class="divider my-4"></div>
              <div v-if="form.easy_compta_siret_addon_active == 1">
                <div class="grid grid-cols-2 gap-4">
                  <div class="ecwp-group form-control indicator">
                    <label class="ecwp-label label" for="company-code">{{
                      translations.siret_api_token
                    }}</label>
                    <span class="indicator-item badge mt-5 border-blue-700"
                      ><a
                        href="https://api.gouv.fr/les-api/sirene_v3"
                        target="_blank"
                        >?</a
                      ></span
                    >
                    <input
                      type="text"
                      id="company-code"
                      v-model="form.easycompta_siret_token_api"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                </div>
              </div>
              <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary rounded-full">
                  <i class="far fa-save"></i> {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
          <div v-if="selectedTab === 2">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.system_settings }}
            </h2>
            <form @submit.prevent="handleSubmit">
              <div class="form-control mt-4 mb-1">
                <label class="cursor-pointer">
                  <span class="label-text mr-2 font-bold">{{
                    translations.activate_logo_mentions
                  }}</span>
                  <input
                    type="checkbox"
                    :checked="form.logo_mentions_active == 1"
                    @change="updateLogoMentionsActive"
                    class="wcpa-ui-toggle"
                  />
                </label>
              </div>
              <div
                v-if="form.logo_mentions_active == 1"
                class="ecwp-group form-control"
              >
                <label class="ecwp-label label" for="logo-mentions">{{
                  translations.logo_mentions
                }}</label>
                <input
                  type="text"
                  id="logo-mentions"
                  v-model="form.logo_mentions"
                  class="ecwp-input input input-bordered"
                  required
                />
              </div>
              <div class="ecwp-group form-control">
                <label class="label">{{ translations.company_logo }}</label>
                <div class="ecwp-file">
                  <input
                    id="file_logo"
                    type="file"
                    @change="handleLogoUpload"
                    accept="image/*"
                    class="ecwp-file-input file-input file-input-bordered file-input-info w-full max-w-xs"
                  />
                  <label for="file_logo">
                    <span>
                      <i class="fas fa-cloud-upload-alt mr-2"></i>
                      {{ translations.select }}
                    </span>
                  </label>
                </div>
                <div v-if="logoPreviewUrl" class="max-w-md">
                  <input
                    type="range"
                    min="0"
                    max="400"
                    v-model="form.logo_width"
                    class="range mt-4"
                    @change="updatePreviewWidth"
                  />
                  <div class="py-2 font-bold">{{ form.logo_width }} px</div>
                  <div v-if="form.logo_width !== null">
                    <img
                      :src="logoPreviewUrl"
                      alt="Logo Preview"
                      class="mb-6"
                      :style="{ width: form.logo_width + 'px' }"
                    />
                  </div>
                </div>
              </div>

              <div class="ecwp-group form-control">
                <label class="ecwp-label label">{{
                  translations.default_currency
                }}</label>
                <select
                  v-model="form.default_currency"
                  class="ecwp-input input input-bordered"
                >
                  <option
                    v-for="currency in currencies"
                    :value="currency.id"
                    :key="currency.id"
                  >
                    {{ currency.name }} ({{ currency.symbol }})
                  </option>
                </select>
              </div>

              <div class="ecwp-group form-control">
                <label class="ecwp-label label">{{
                  translations.currency_position
                }}</label>
                <select
                  v-model="form.currency_position"
                  class="ecwp-input input input-bordered"
                >
                  <option value="before">
                    {{ translations.before_amount }}
                  </option>
                  <option value="after">{{ translations.after_amount }}</option>
                </select>
              </div>

              <div class="form-control mt-4 mb-1">
                <label class="cursor-pointer">
                  <span class="label-text mr-2 font-bold">{{
                    translations.activate_vat
                  }}</span>
                  <input
                    type="checkbox"
                    :checked="form.vat_active == 1"
                    @change="updateVatActive"
                    class="wcpa-ui-toggle"
                  />
                </label>
              </div>
              <div v-if="form.vat_active == 1" class="ecwp-group form-control">
                <label class="ecwp-label label">{{
                  translations.default_vat
                }}</label>
                <select
                  v-model="form.default_vat"
                  class="ecwp-input input input-bordered"
                >
                  <option value="0"></option>
                  <option v-for="vat in vats" :value="vat.id" :key="vat.id">
                    {{ vat.description }} - {{ vat.rate }}%
                  </option>
                </select>
              </div>

              <div class="ecwp-group form-control mt-2">
                <label class="ecwp-label label">{{
                  translations.format_date
                }}</label>
                <select
                  v-model="form.date_format"
                  class="ecwp-input input input-bordered"
                >
                  <option value="DD-MM-YYYY" selected="selected">
                    DD-MM-YYYY
                  </option>
                  <option value="MM-DD-YYYY">MM-DD-YYYY</option>
                  <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                  <option value="YYYY/MM/DD">YYYY/MM/DD</option>
                  <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                  <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                  <option value="YYYY.MM.DD">YYYY.MM.DD</option>
                  <option value="DD.MM.YYYY">DD.MM.YYYY</option>
                  <option value="MM.DD.YYYY">MM.DD.YYYY</option>
                </select>
              </div>
              <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary rounded-full">
                  <i class="far fa-save"></i> {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
          <div v-if="selectedTab === 3">
            <dialog v-if="showArticleModal" id="modal_article" class="modal">
              <div class="modal-box">
                <h3>
                  {{ editingArticle ? translations.edit : translations.add }}
                </h3>
                <form @submit.prevent="saveArticle">
                  <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="closeArticleModal"
                  >
                    ✕
                  </button>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="name_ref">{{
                      translations.item_ref
                    }}</label>
                    <input
                      type="text"
                      id="name_ref"
                      v-model="articleForm.ref"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="name_article">{{
                      translations.item_name
                    }}</label>
                    <input
                      type="text"
                      id="name_article"
                      v-model="articleForm.name"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="desc_article">{{
                      translations.description
                    }}</label>
                    <textarea
                      id="desc_article"
                      v-model="articleForm.description"
                      class="ecwp-input input input-bordered"
                      required
                    ></textarea>
                  </div>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="price_article">{{
                      translations.unit_price
                    }}</label>
                    <input
                      type="text"
                      id="price_article"
                      v-model="articleForm.unit_price"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>

                  <div class="form-group mt-4 flex justify-end">
                    <button
                      type="button"
                      class="btn btn-secondary rounded-full"
                      @click="closeArticleModal"
                    >
                      {{ translations.cancel }}
                    </button>
                    <button
                      type="submit"
                      class="btn btn-primary rounded-full mx-2"
                    >
                      {{
                        editingArticle ? translations.save : translations.add
                      }}
                    </button>
                  </div>
                </form>
              </div>
            </dialog>
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold">
                {{ translations.articles_settings }}
              </h3>
              <button class="btn btn-primary rounded-full" @click="addArticle">
                <i class="fas fa-plus mr-2"></i>
                {{ translations.add }}
              </button>
            </div>

            <div class="table-container">
              <table class="table w-full">
                <thead>
                  <tr>
                    <th>{{ translations.item_ref }}</th>
                    <th>{{ translations.name }}</th>
                    <th>{{ translations.description }}</th>
                    <th>{{ translations.unit_price }}</th>
                    <th>{{ translations.actions }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="article in articles" :key="article.id">
                    <td>{{ article.ref }}</td>
                    <td>{{ article.name }}</td>
                    <td>{{ article.description }}</td>
                    <td>{{ article.unit_price }}</td>
                    <td>
                      <button
                        class="p-2 text-secondary"
                        @click="editArticle(article.id)"
                      >
                        <i class="fas fa-edit"></i>
                      </button>
                      <button
                        class="p-2 text-error"
                        @click="delete_item('article', article.id)"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="divider mt-2 mb-4"></div>
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.categories }}
            </h2>
            <div class="table-container">
              <table class="table w-full">
                <thead>
                  <tr>
                    <th>{{ translations.name }}</th>
                    <th>{{ translations.actions }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="category in categories" :key="category.id">
                    <td>{{ category.name }}</td>
                    <td>
                      <button
                        class="p-2 text-error"
                        @click="delete_item('category_article', category.id)"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-if="selectedTab === 4">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.invoices_settings }}
            </h2>
            <form @submit.prevent="handleSubmit">
              <div class="grid grid-cols-2 gap-4">
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="invoice-color">{{
                    translations.invoice_color
                  }}</label>
                  <input
                    type="text"
                    id="invoice-color"
                    v-model="form.invoice_color"
                    class="ecwp-input input input-bordered"
                    required
                  />
                  <color-input v-model="form.invoice_color" />
                </div>

                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="invoice-prefix">{{
                    translations.invoice_prefix
                  }}</label>
                  <input
                    type="text"
                    id="invoice-prefix"
                    v-model="form.invoice_prefix"
                    class="ecwp-input input input-bordered"
                    required
                  />
                </div>
              </div>

              <div class="divider my-4"></div>

              <div class="grid grid-cols-1 gap-4">
                <div class="form-control mt-4 mb-1">
                  <label class="cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="form.active_disbursements == 1"
                      @change="updateFormField($event, 'active_disbursements')"
                      class="wcpa-ui-toggle"
                    />
                    <span class="label-text mr-2 font-bold">{{
                      translations.active_disbursements
                    }}</span>
                  </label>
                </div>
              </div>
              <div class="divider my-4"></div>

              <div class="grid grid-cols-2 gap-4">
                <div class="form-control mt-4 mb-1">
                  <label class="cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="form.show_phone == 1"
                      @change="updateFormField($event, 'show_phone')"
                      class="wcpa-ui-toggle"
                    />
                    <span class="label-text mr-2 font-bold">{{
                      translations.show_phone
                    }}</span>
                  </label>
                </div>
                <div class="form-control mt-4 mb-1">
                  <label class="cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="form.show_email == 1"
                      @change="updateFormField($event, 'show_email')"
                      class="wcpa-ui-toggle"
                    />
                    <span class="label-text mr-2 font-bold">{{
                      translations.show_email
                    }}</span>
                  </label>
                </div>
                <div class="form-control mt-4 mb-1">
                  <label class="cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="form.show_siren == 1"
                      @change="updateFormField($event, 'show_siren')"
                      class="wcpa-ui-toggle"
                    />
                    <span class="label-text mr-2 font-bold">{{
                      translations.show_siren
                    }}</span>
                  </label>
                </div>
                <div class="form-control mt-4 mb-1">
                  <label class="cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="form.show_tax_number == 1"
                      @change="updateFormField($event, 'show_tax_number')"
                      class="wcpa-ui-toggle"
                    />
                    <span class="label-text mr-2 font-bold">{{
                      translations.show_tax_number
                    }}</span>
                  </label>
                </div>
                <div class="form-control mt-4 mb-1">
                  <label class="cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="form.show_watermark == 1"
                      @change="updateFormField($event, 'show_watermark')"
                      class="wcpa-ui-toggle"
                    />
                    <span class="label-text mr-2 font-bold">{{
                      translations.show_watermark
                    }}</span>
                  </label>
                </div>
                <div
                  class="form-control mt-4 mb-1"
                  v-if="form.show_watermark == 1"
                >
                  <label class="cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="form.show_watermark_only_paid == 1"
                      @change="
                        updateFormField($event, 'show_watermark_only_paid')
                      "
                      class="wcpa-ui-toggle"
                    />
                    <span class="label-text mr-2 font-bold">{{
                      translations.show_watermark_only_paid
                    }}</span>
                  </label>
                </div>
              </div>

              <div class="divider my-4"></div>

              <div class="grid grid-cols-2 gap-4">
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="conditions-payment">{{
                    translations.payment_conditions
                  }}</label>
                  <input
                    type="text"
                    id="conditions-payment"
                    v-model="form.payment_conditions"
                    class="ecwp-input input input-bordered"
                  />
                </div>

                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="mode-payment">{{
                    translations.payment_mode
                  }}</label>
                  <input
                    type="text"
                    id="mode-payment"
                    v-model="form.payment_mode"
                    class="ecwp-input input input-bordered"
                  />
                </div>
              </div>
              <div class="grid grid-cols-1 gap-4">
                <div class="form-control">
                  <label class="ecwp-label label" for="invoice-prefix">{{
                    translations.invoice_terms
                  }}</label>
                  <div>
                    <vue-editor
                      v-model="form.invoice_terms"
                      :editorToolbar="toolbarOptions"
                    ></vue-editor>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="invoice-iban">{{
                      translations.invoice_iban
                    }}</label>
                    <input
                      type="text"
                      id="invoice-iban"
                      v-model="form.invoice_iban"
                      class="ecwp-input input input-bordered"
                    />
                  </div>

                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="invoice-bic">{{
                      translations.invoice_bic
                    }}</label>
                    <input
                      type="text"
                      id="invoice-bic"
                      v-model="form.invoice_bic"
                      class="ecwp-input input input-bordered"
                    />
                  </div>
                </div>

                <div class="form-control">
                  <label class="ecwp-label label" for="invoice-prefix">{{
                    translations.invoice_footer
                  }}</label>
                  <div>
                    <vue-editor
                      v-model="form.invoice_footer"
                      :editorToolbar="toolbarOptions"
                    ></vue-editor>
                  </div>
                </div>
              </div>
              <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary rounded-full">
                  <i class="far fa-save"></i> {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
          <div v-if="selectedTab === 5">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.credits_settings }}
            </h2>
            <form @submit.prevent="handleSubmit">
              <div class="grid grid-cols-2 gap-4">
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="credit-color">{{
                    translations.credit_color
                  }}</label>
                  <input
                    type="text"
                    id="credit-color"
                    v-model="form.credit_color"
                    class="ecwp-input input input-bordered"
                    required
                  />
                  <color-input v-model="form.credit_color" />
                </div>

                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="credit-prefix">{{
                    translations.credit_prefix
                  }}</label>
                  <input
                    type="text"
                    id="credit-prefix"
                    v-model="form.credit_prefix"
                    class="ecwp-input input input-bordered"
                    required
                  />
                </div>
              </div>
              <div class="grid grid-cols-1 gap-4">
                <div class="form-control">
                  <label class="ecwp-label label" for="credit-prefix">{{
                    translations.credit_footer
                  }}</label>
                  <div>
                    <vue-editor
                      v-model="form.credit_footer"
                      :editorToolbar="toolbarOptions"
                    ></vue-editor>
                  </div>
                </div>

                <div class="form-control">
                  <label class="ecwp-label label" for="credit-prefix">{{
                    translations.credit_terms
                  }}</label>
                  <div>
                    <vue-editor
                      v-model="form.credit_terms"
                      :editorToolbar="toolbarOptions"
                    ></vue-editor>
                  </div>
                </div>
              </div>
              <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary rounded-full">
                  <i class="far fa-save"></i> {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
          <div v-if="selectedTab === 6">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.quotes_settings }}
            </h2>
            <form @submit.prevent="handleSubmit">
              <div class="grid grid-cols-2 gap-4">
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="quote-color">{{
                    translations.quote_color
                  }}</label>
                  <input
                    type="text"
                    id="quote-color"
                    v-model="form.quote_color"
                    class="ecwp-input input input-bordered"
                    required
                  />
                  <color-input v-model="form.quote_color" />
                </div>

                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="quote-prefix">{{
                    translations.quote_prefix
                  }}</label>
                  <input
                    type="text"
                    id="quote-prefix"
                    v-model="form.quote_prefix"
                    class="ecwp-input input input-bordered"
                    required
                  />
                </div>
              </div>
              <div class="grid grid-cols-1 gap-4">
                <div class="form-control">
                  <label class="ecwp-label label" for="quote-prefix">{{
                    translations.quote_footer
                  }}</label>
                  <div>
                    <vue-editor
                      v-model="form.quote_footer"
                      :editorToolbar="toolbarOptions"
                    ></vue-editor>
                  </div>
                </div>

                <div class="form-control">
                  <label class="ecwp-label label" for="quote-prefix">{{
                    translations.quote_terms
                  }}</label>
                  <div>
                    <vue-editor
                      v-model="form.quote_terms"
                      :editorToolbar="toolbarOptions"
                    ></vue-editor>
                  </div>
                </div>
              </div>
              <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary rounded-full">
                  <i class="far fa-save"></i> {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
          <div v-if="selectedTab === 7">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.currency_vat_settings }}
            </h2>
            <dialog v-if="showCurrencyModal" id="modal_currency" class="modal">
              <div class="modal-box">
                <h3>
                  {{ editingCurrency ? translations.edit : translations.add }}
                </h3>
                <form @submit.prevent="saveCurrency">
                  <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="closeCurrencyModal"
                  >
                    ✕
                  </button>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="name_currency">{{
                      translations.name
                    }}</label>
                    <input
                      type="text"
                      id="name_currency"
                      v-model="currencyForm.name"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="symbol_currency">{{
                      translations.symbol
                    }}</label>
                    <input
                      type="text"
                      id="symbol_currency"
                      v-model="currencyForm.symbol"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="code_currency">{{
                      translations.code
                    }}</label>
                    <input
                      type="text"
                      id="code_currency"
                      v-model="currencyForm.code"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>

                  <div class="form-group mt-4 flex justify-end">
                    <button
                      type="button"
                      class="btn btn-secondary rounded-full"
                      @click="closeCurrencyModal"
                    >
                      {{ translations.cancel }}
                    </button>
                    <button
                      type="submit"
                      class="btn btn-primary rounded-full mx-2"
                    >
                      {{
                        editingCurrency ? translations.save : translations.add
                      }}
                    </button>
                  </div>
                </form>
              </div>
            </dialog>

            <!-- Currencies Section -->
            <div class="mb-8">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                  {{ translations.currencies }}
                </h3>
                <button
                  class="btn btn-primary rounded-full"
                  @click="addCurrency"
                >
                  <i class="fas fa-plus mr-2"></i>
                  {{ translations.add_currency }}
                </button>
              </div>
              <div class="table-container">
                <table class="table w-full">
                  <thead>
                    <tr>
                      <th>{{ translations.name }}</th>
                      <th>{{ translations.symbol }}</th>
                      <th>{{ translations.code }}</th>
                      <th>{{ translations.actions }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="currency in currencies" :key="currency.id">
                      <td>{{ currency.name }}</td>
                      <td>{{ currency.symbol }}</td>
                      <td>{{ currency.code }}</td>
                      <td>
                        <button
                          class="p-2 text-secondary"
                          @click="editCurrency(currency.id)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          class="p-2 text-error"
                          @click="delete_item('currency', currency.id)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- VAT Section -->
            <div>
              <dialog v-if="showVATModal" id="modal_vat" class="modal">
                <div class="modal-box">
                  <h3>
                    {{ editingVAT ? translations.edit : translations.add }}
                  </h3>
                  <form @submit.prevent="saveVAT">
                    <button
                      class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                      @click="closeVATModal"
                    >
                      ✕
                    </button>
                    <div class="ecwp-group form-control">
                      <label class="ecwp-label label" for="vat-description">{{
                        translations.description
                      }}</label>
                      <input
                        type="text"
                        id="vat-description"
                        v-model="vatForm.description"
                        class="ecwp-input input input-bordered"
                        required
                      />
                    </div>
                    <div class="ecwp-group form-control">
                      <label class="ecwp-label label" for="vat-rate"
                        >{{ translations.rate }} (%)</label
                      >
                      <input
                        type="text"
                        id="vat-rate"
                        v-model="vatForm.rate"
                        class="ecwp-input input input-bordered"
                        required
                      />
                    </div>
                    <div class="form-group mt-4 flex justify-end">
                      <button
                        type="button"
                        class="btn btn-secondary rounded-full"
                        @click="closeVATModal"
                      >
                        {{ translations.cancel }}
                      </button>
                      <button
                        type="submit"
                        class="btn btn-primary rounded-full mx-2"
                      >
                        {{ editingVAT ? translations.save : translations.add }}
                      </button>
                    </div>
                  </form>
                </div>
              </dialog>
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                  {{ translations.vat_rates }}
                </h3>
                <button class="btn btn-primary rounded-full" @click="addVAT">
                  <i class="fas fa-plus mr-2"></i> {{ translations.add_vat }}
                </button>
              </div>
              <div class="table-container">
                <table class="table w-full">
                  <thead>
                    <tr>
                      <th>{{ translations.description }}</th>
                      <th>{{ translations.rate }} (%)</th>
                      <th>{{ translations.actions }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="vat in vats" :key="vat.id">
                      <td>{{ vat.description }}</td>
                      <td>{{ vat.rate }}</td>
                      <td>
                        <button
                          class="p-2 text-secondary"
                          @click="editVAT(vat.id)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          class="p-2 text-error"
                          @click="delete_item('vat', vat.id)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div v-if="selectedTab === 8">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.payments_settings }}
            </h2>
            <dialog v-if="showPaymentModal" id="modal_payments" class="modal">
              <div class="modal-box">
                <h3>
                  {{ editingPayment ? translations.edit : translations.add }}
                </h3>
                <form @submit.prevent="savePayment">
                  <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="closePaymentModal"
                  >
                    ✕
                  </button>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="name_payment">{{
                      translations.name
                    }}</label>
                    <input
                      type="text"
                      id="name_payment"
                      v-model="paymentForm.method_name"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>

                  <div class="form-group mt-4 flex justify-end">
                    <button
                      type="button"
                      class="btn btn-secondary rounded-full"
                      @click="closePaymentModal"
                    >
                      {{ translations.cancel }}
                    </button>
                    <button
                      type="submit"
                      class="btn btn-primary rounded-full mx-2"
                    >
                      {{
                        editingPayment ? translations.save : translations.add
                      }}
                    </button>
                  </div>
                </form>
              </div>
            </dialog>

            <!-- Expenses Section -->
            <div class="mb-8">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                  {{ translations.payments_methods }}
                </h3>
                <button
                  class="btn btn-primary rounded-full"
                  @click="addPayment"
                >
                  <i class="fas fa-plus mr-2"></i>{{ translations.add_method }}
                </button>
              </div>
              <div class="table-container">
                <table class="table w-full">
                  <thead>
                    <tr>
                      <th>{{ translations.id }}</th>
                      <th>{{ translations.name }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="payment in payments" :key="payment.id">
                      <td>{{ payment.id }}</td>
                      <td>{{ payment.method_name }}</td>
                      <td>
                        <button
                          class="p-2 text-secondary"
                          @click="editPayment(payment.id)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          class="p-2 text-error"
                          @click="delete_item('payment', payment.id)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div v-if="selectedTab === 9">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.expenses_settings }}
            </h2>
            <dialog v-if="showExpenseModal" id="modal_expenses" class="modal">
              <div class="modal-box">
                <h3>
                  {{ editingExpense ? translations.edit : translations.add }}
                </h3>
                <form @submit.prevent="saveExpCat">
                  <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="closeExpenseModal"
                  >
                    ✕
                  </button>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="name_expense">{{
                      translations.name
                    }}</label>
                    <input
                      type="text"
                      id="name_expense"
                      v-model="expenseForm.name"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>

                  <div class="form-group mt-4 flex justify-end">
                    <button
                      type="button"
                      class="btn btn-secondary rounded-full"
                      @click="closeExpenseModal"
                    >
                      {{ translations.cancel }}
                    </button>
                    <button
                      type="submit"
                      class="btn btn-primary rounded-full mx-2"
                    >
                      {{
                        editingExpense ? translations.save : translations.add
                      }}
                    </button>
                  </div>
                </form>
              </div>
            </dialog>

            <!-- Expenses Section -->
            <div class="mb-8">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                  {{ translations.expenses_categories }}
                </h3>
                <button class="btn btn-primary rounded-full" @click="addExpCat">
                  <i class="fas fa-plus mr-2"></i>
                  {{ translations.add_category }}
                </button>
              </div>
              <div class="table-container">
                <table class="table w-full">
                  <thead>
                    <tr>
                      <th>{{ translations.id }}</th>
                      <th>{{ translations.name }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="expense in expenses" :key="expense.id">
                      <td>{{ expense.id }}</td>
                      <td>{{ expense.name }}</td>
                      <td>
                        <button
                          class="p-2 text-secondary"
                          @click="editExpCat(expense.id)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          class="p-2 text-error"
                          @click="delete_item('expense', expense.id)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div v-if="selectedTab === 10">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.planning_settings }}
            </h2>
            <dialog v-if="showPlanningModal" id="modal_planning" class="modal">
              <div class="modal-box">
                <h3>
                  {{ editingPlanning ? translations.edit : translations.add }}
                </h3>
                <form @submit.prevent="savePlanningCat">
                  <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="closePlanningModal"
                  >
                    ✕
                  </button>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="name_planning">{{
                      translations.name
                    }}</label>
                    <input
                      type="text"
                      id="name_planning"
                      v-model="planningForm.name"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="background_planning">{{
                      translations.background
                    }}</label>
                    <input
                      type="text"
                      id="background_planning"
                      v-model="planningForm.background"
                      class="ecwp-input input input-bordered"
                      required
                    />
                    <color-input v-model="planningForm.background" />
                  </div>
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="color_planning">{{
                      translations.text_color
                    }}</label>
                    <input
                      type="text"
                      id="color_planning"
                      v-model="planningForm.color"
                      class="ecwp-input input input-bordered"
                      required
                    />
                    <color-input v-model="planningForm.color" />
                  </div>

                  <div class="form-group mt-4 flex justify-end">
                    <button
                      type="button"
                      class="btn btn-secondary rounded-full"
                      @click="closePlanningModal"
                    >
                      {{ translations.cancel }}
                    </button>
                    <button
                      type="submit"
                      class="btn btn-primary rounded-full mx-2"
                      :disabled="loading"
                    >
                      {{
                        editingPlanning ? translations.save : translations.add
                      }}
                      <span
                        v-if="loading"
                        class="loading loading-spinner loading-sm"
                      ></span>
                    </button>
                  </div>
                </form>
              </div>
            </dialog>

            <!-- planning Section -->
            <div class="mb-8">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                  {{ translations.planning_categories }}
                </h3>
                <button
                  class="btn btn-primary rounded-full"
                  @click="addPlanningCat"
                >
                  <i class="fas fa-plus mr-2"></i>
                  {{ translations.add_category }}
                </button>
              </div>
              <div class="table-container">
                <table class="table w-full">
                  <thead>
                    <tr>
                      <th>{{ translations.id }}</th>
                      <th>{{ translations.name }}</th>
                      <th>{{ translations.background }}</th>
                      <th>{{ translations.color }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="p in planning" :key="p.id">
                      <td>{{ p.id }}</td>
                      <td>{{ p.name }}</td>
                      <td>
                        <span
                          class="ecwp-color-preview"
                          :style="{ backgroundColor: p.background }"
                        ></span>
                      </td>
                      <td>
                        <span
                          class="ecwp-color-preview"
                          :style="{ backgroundColor: p.color }"
                        ></span>
                      </td>

                      <td>
                        <button
                          class="p-2 text-secondary"
                          @click="editPlanningCat(p.id)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          class="p-2 text-error"
                          @click="delete_item('planning', p.id)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div v-if="selectedTab === 11">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.email_settings }}
            </h2>
            <form @submit.prevent="handleSubmit">
              <div class="form-control mt-4 mb-4">
                <label class="cursor-pointer">
                  <span class="label-text mr-2 font-bold">{{
                    translations.email_log_active
                  }}</span>
                  <input
                    type="checkbox"
                    :checked="form.email_log_active == 1"
                    @change="updateEmailLogsActive"
                    class="wcpa-ui-toggle"
                  />
                </label>
              </div>
              <div class="divider mt-2 mb-4"></div>
              <div role="tablist" class="tabs tabs-boxed">
                <a
                  role="tab"
                  :class="['tab', { 'tab-active': activeTabEmail === 'tab1' }]"
                  @click="setActiveTab('tab1')"
                >
                  {{ translations.email_invoice }}
                </a>
                <a
                  role="tab"
                  :class="['tab', { 'tab-active': activeTabEmail === 'tab2' }]"
                  @click="setActiveTab('tab2')"
                >
                  {{ translations.email_quote }}
                </a>
                <a
                  role="tab"
                  :class="['tab', { 'tab-active': activeTabEmail === 'tab3' }]"
                  @click="setActiveTab('tab3')"
                >
                  {{ translations.invoice_reminder }}
                </a>
                <a
                  role="tab"
                  :class="['tab', { 'tab-active': activeTabEmail === 'tab4' }]"
                  @click="setActiveTab('tab4')"
                >
                  {{ translations.payment_received }}
                </a>
              </div>

              <div v-if="activeTabEmail === 'tab1'" class="p-4">
                <div class="grid grid-cols-1 gap-4">
                  <div class="ecwp-group form-control">
                    <label
                      class="ecwp-label label"
                      for="email_invoice_subject"
                      >{{ translations.email_subject }}</label
                    >
                    <input
                      type="text"
                      id="email_invoice_subject"
                      v-model="form.email_invoice_subject"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>

                  <div class="form-control">
                    <label class="ecwp-label label">{{
                      translations.email_content
                    }}</label>
                    <div>
                      <vue-editor
                        v-model="form.email_invoice_content"
                        :editorToolbar="toolbarOptions"
                      ></vue-editor>
                    </div>
                    <div class="mockup-code bg-base-900 mt-4">
                      <pre><b>{REF}</b></pre>
                      <pre><b>{CLIENT}</b></pre>
                      <pre><b>{DUE_DATE}</b></pre>
                      <pre><b>{AMOUNT}</b></pre>
                      <pre><b>{CURRENCY}</b></pre>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="activeTabEmail === 'tab2'" class="p-4">
                <div class="grid grid-cols-1 gap-4">
                  <div class="ecwp-group form-control">
                    <label class="ecwp-label label" for="email_quote_subject">{{
                      translations.email_subject
                    }}</label>
                    <input
                      type="text"
                      id="email_quote_subject"
                      v-model="form.email_quote_subject"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>

                  <div class="form-control">
                    <label class="ecwp-label label">{{
                      translations.email_content
                    }}</label>
                    <div>
                      <vue-editor
                        v-model="form.email_quote_content"
                        :editorToolbar="toolbarOptions"
                      ></vue-editor>
                    </div>
                    <div class="mockup-code bg-base-900 mt-4">
                      <pre><b>{REF}</b></pre>
                      <pre><b>{CLIENT}</b></pre>
                      <pre><b>{AMOUNT}</b></pre>
                      <pre><b>{CURRENCY}</b></pre>
                      <pre><b>{CREATED_DATE}</b></pre>
                      <pre><b>{DUE_DATE}</b></pre>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="activeTabEmail === 'tab3'" class="p-4">
                <div class="grid grid-cols-1 gap-4">
                  <div class="ecwp-group form-control">
                    <label
                      class="ecwp-label label"
                      for="remind_invoice_subject"
                      >{{ translations.email_subject }}</label
                    >
                    <input
                      type="text"
                      id="remind_invoice_subject"
                      v-model="form.remind_invoice_subject"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>

                  <div class="form-control">
                    <label class="ecwp-label label">{{
                      translations.email_content
                    }}</label>
                    <div>
                      <vue-editor
                        v-model="form.remind_invoice_content"
                        :editorToolbar="toolbarOptions"
                      ></vue-editor>
                    </div>
                    <div class="mockup-code bg-base-900 mt-4">
                      <pre><b>{REF}</b></pre>
                      <pre><b>{CLIENT}</b></pre>
                      <pre><b>{DUE_DATE}</b></pre>
                      <pre><b>{AMOUNT}</b></pre>
                      <pre><b>{CURRENCY}</b></pre>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="activeTabEmail === 'tab4'" class="p-4">
                <div role="alert" class="alert shadow">
                  <i class="fas fa-exclamation-circle"></i>
                  <div>
                    <h2 class="text-xl text-center my-4">
                      {{ translations.coming_soon }}
                    </h2>
                  </div>
                </div>
              </div>

              <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary rounded-full">
                  <i class="far fa-save"></i> {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
          <div v-if="selectedTab === 12">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.users_settings }}
            </h2>
            <div role="tablist" class="tabs tabs-boxed">
              <a
                role="tab"
                :class="['tab', { 'tab-active': activeTabUsers === 'user1' }]"
                @click="setActiveTabUsers('user1')"
              >
                Automatic create account
              </a>
              <a
                role="tab"
                :class="['tab', { 'tab-active': activeTabUsers === 'user2' }]"
                @click="setActiveTabUsers('user2')"
              >
                Lost password
              </a>
            </div>

            <div v-if="activeTabUsers === 'user1'" class="p-4">
              <form @submit.prevent="handleSubmit">
                <div class="grid grid-cols-1 gap-4">
                  <div class="ecwp-group form-control">
                    <label
                      class="ecwp-label label"
                      for="email_create_account_subject"
                      >{{ translations.email_subject }}</label
                    >
                    <input
                      type="text"
                      id="email_create_account_subject"
                      v-model="form.email_create_account_subject"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>

                  <div class="form-control">
                    <label class="ecwp-label label">{{
                      translations.email_content
                    }}</label>
                    <div>
                      <vue-editor
                        v-model="form.email_create_account_content"
                        :editorToolbar="toolbarOptions"
                      ></vue-editor>
                    </div>
                    <div class="mockup-code bg-base-900 mt-4">
                      <pre><b>{CLIENT}</b></pre>
                      <pre><b>{USERNAME}</b></pre>
                      <pre><b>{PASSWORD}</b></pre>
                    </div>
                  </div>
                </div>
                <div class="mt-6 flex justify-end">
                  <button type="submit" class="btn btn-primary rounded-full">
                    <i class="far fa-save"></i> {{ translations.save }}
                  </button>
                </div>
              </form>
            </div>
            <div v-if="activeTabUsers === 'user2'" class="p-4">
              <div role="alert" class="alert shadow">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                  <h2 class="text-xl text-center my-4">
                    {{ translations.coming_soon }}
                  </h2>
                </div>
              </div>
            </div>
          </div>
          <div v-if="selectedTab === 13">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.stripe_settings }}
            </h2>
            <form @submit.prevent="handleSubmit">
              <div class="grid grid-cols-1 gap-4">
                <!-- Mode Selector -->
                <div class="ecwp-group form-control">
                  <label class="ecwp-label label" for="stripe-mode">
                    {{ translations.stripe_mode }}
                  </label>
                  <select
                    id="stripe-mode"
                    v-model="form.stripe_mode"
                    class="ecwp-input input input-bordered"
                    required
                  >
                    <option value="test">{{ translations.test_mode }}</option>
                    <option value="live">{{ translations.live_mode }}</option>
                  </select>
                </div>

                <!-- Stripe Keys for Test Mode -->
                <div v-if="form.stripe_mode === 'test'">
                  <div class="ecwp-group form-control">
                    <label
                      class="ecwp-label label"
                      for="stripe-public-key-test"
                    >
                      {{ translations.stripe_public_key_test }}
                    </label>
                    <input
                      type="text"
                      id="stripe-public-key-test"
                      v-model="form.stripe_public_key_test"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                  <div class="ecwp-group form-control">
                    <label
                      class="ecwp-label label"
                      for="stripe-secret-key-test"
                    >
                      {{ translations.stripe_secret_key_test }}
                    </label>
                    <input
                      type="text"
                      id="stripe-secret-key-test"
                      v-model="form.stripe_secret_key_test"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                </div>

                <!-- Stripe Keys for Live Mode -->
                <div v-if="form.stripe_mode === 'live'">
                  <div class="ecwp-group form-control">
                    <label
                      class="ecwp-label label"
                      for="stripe-public-key-live"
                    >
                      {{ translations.stripe_public_key_live }}
                    </label>
                    <input
                      type="text"
                      id="stripe-public-key-live"
                      v-model="form.stripe_public_key_live"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                  <div class="ecwp-group form-control">
                    <label
                      class="ecwp-label label"
                      for="stripe-secret-key-live"
                    >
                      {{ translations.stripe_secret_key_live }}
                    </label>
                    <input
                      type="text"
                      id="stripe-secret-key-live"
                      v-model="form.stripe_secret_key_live"
                      class="ecwp-input input input-bordered"
                      required
                    />
                  </div>
                </div>
              </div>
              <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary rounded-full">
                  <i class="far fa-save"></i> {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
          <div v-if="selectedTab === 14">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.stats_settings }}
            </h2>
            <form @submit.prevent="handleSubmit">
              <div class="grid grid-cols-1 gap-4">
                <div class="ecwp-group form-control mt-2">
                  <label class="ecwp-label label" for="limit-declaration">{{
                    translations.limit_declaration
                  }}</label>
                  <input
                    type="number"
                    id="limit-declaration"
                    v-model="form.limit_declaration"
                    class="ecwp-input input input-bordered"
                    required
                  />
                </div>

                <div class="ecwp-group form-control mt-2">
                  <label class="ecwp-label label" for="limit-tva">{{
                    translations.limit_tva
                  }}</label>
                  <input
                    type="number"
                    id="limit-tva"
                    v-model="form.limit_tva"
                    class="ecwp-input input input-bordered"
                    required
                  />
                </div>
              </div>
              <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary rounded-full">
                  <i class="far fa-save"></i> {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
          <div v-if="selectedTab === 15">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.qrcode_settings }}
            </h2>
            <form @submit.prevent="handleSubmit">
              <div class="grid grid-cols-1 gap-4">
                <div class="ecwp-group form-control mt-2">
                  <label class="ecwp-label label" for="stripe-api">{{
                    translations.stripe_api
                  }}</label>
                  <input
                    type="text"
                    id="stripe-api"
                    v-model="form.easy_compta_stripe_secret_api"
                    class="ecwp-input input input-bordered"
                    required
                  />
                </div>
              </div>
              <div class="mt-6 flex justify-end">
                <button type="submit" class="btn btn-primary rounded-full">
                  <i class="far fa-save"></i> {{ translations.save }}
                </button>
              </div>
            </form>
          </div>
          <div v-if="selectedTab === 16">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.validation_license }}
            </h2>
            
            <!-- Formulaire de validation compact -->
            <div v-if="!licenseData || !licenseData.valid" class="mb-4">
              <div class="form-control">
                <label class="label py-1">
                  <span class="label-text text-sm font-medium">{{
                    translations.license_key
                  }}</span>
                </label>
                <div class="input-group">
                  <input
                    type="text"
                    id="license-code"
                    v-model="license_key"
                    class="input input-bordered input-sm flex-1"
                    :disabled="licenseData && licenseData.valid"
                    :placeholder="translations.enter_license_key || 'XXXX-XXXX-XXXX-XXXX'"
                    required
                  />
                  <button
                    @click="checkLicense"
                    class="btn btn-primary btn-sm"
                    :disabled="loadingLicense || (licenseData && licenseData.valid)"
                  >
                    <span
                      v-if="loadingLicense"
                      class="loading loading-spinner loading-xs"
                    ></span>
                    <i v-else class="fas fa-check mr-1"></i>
                    <span v-if="!loadingLicense">
                      {{ translations.validate }}
                    </span>
                  </button>
                </div>
              </div>
            </div>
            
            <div v-if="errorMessage" class="alert alert-error shadow-lg mb-4 py-3 animate-fade-in">
              <i class="fas fa-exclamation-circle animate-pulse"></i>
              <span class="text-sm font-medium">{{ errorMessage }}</span>
            </div>
              <div v-if="licenseData" class="my-4 animate-fade-in">
                <div class="card bg-gradient-to-br from-base-200 to-base-300 shadow-xl border border-base-300 hover:shadow-2xl transition-all duration-300">
                  <div class="card-body p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                      <div class="p-3 rounded-lg bg-base-100/50 hover:bg-base-100 transition-colors duration-200">
                        <div class="text-xs text-base-content/60 mb-2 font-medium flex items-center gap-1">
                          <i class="fas fa-globe text-primary text-xs"></i>
                          {{ translations.domain || "Domaine" }}
                        </div>
                        <div class="font-bold text-base text-primary">{{ licenseData.domain || licenseData.current_domain }}</div>
                      </div>
                      <div class="p-3 rounded-lg bg-base-100/50 hover:bg-base-100 transition-colors duration-200">
                        <div class="text-xs text-base-content/60 mb-2 font-medium flex items-center gap-1">
                          <i class="fas fa-calendar-check text-info text-xs"></i>
                          {{ translations.activation_date || "Date d'activation" }}
                        </div>
                        <div class="font-bold text-base text-info">{{ licenseData.start_date }}</div>
                      </div>
                      <div class="p-3 rounded-lg bg-base-100/50 hover:bg-base-100 transition-colors duration-200">
                        <div class="text-xs text-base-content/60 mb-2 font-medium flex items-center gap-1">
                          <i class="fas fa-calendar-times text-warning text-xs"></i>
                          {{ translations.expiry_date || "Date d'expiration" }}
                        </div>
                        <div class="font-bold text-base text-warning">{{ licenseData.end_date }}</div>
                      </div>
                      <div class="p-3 rounded-lg bg-base-100/50 hover:bg-base-100 transition-colors duration-200">
                        <div class="text-xs text-base-content/60 mb-2 font-medium flex items-center gap-1">
                          <i class="fas fa-shield-alt text-success text-xs"></i>
                          {{ translations.status || "Statut" }}
                        </div>
                        <div>
                          <span class="badge badge-lg shadow-md animate-pulse" :class="licenseData.valid ? 'badge-success' : 'badge-error'">
                            <i :class="licenseData.valid ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                            {{ licenseData.valid ? (translations.valid || "Valide") : (translations.invalid || "Invalide") }}
                          </span>
                        </div>
                      </div>
                    </div>
                    <div class="flex gap-3 mt-4 pt-4 border-t border-base-300">
                      <button
                        @click="refresh_licence()"
                        class="btn btn-sm btn-primary shadow-md hover:shadow-lg hover:scale-105 transition-all duration-200"
                      >
                        <i class="fas fa-sync mr-2"></i>
                        {{ translations.refresh || "Rafraîchir" }}
                      </button>
                      <button
                        @click="delete_item('licence', '')"
                        class="btn btn-sm btn-error btn-outline shadow-md hover:shadow-lg hover:scale-105 transition-all duration-200"
                      >
                        <i class="fas fa-trash mr-2"></i>
                        {{ translations.delete || "Supprimer" }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section de gestion des domaines autorisés -->
              <div v-if="licenseData" class="mt-6 animate-fade-in">
                <div class="card bg-gradient-to-br from-base-100 via-base-100 to-base-200 shadow-2xl border-2 border-primary/20 hover:border-primary/40 transition-all duration-300">
                  <div class="card-body px-0">
                    <div class="flex items-center justify-between mb-6">
                      <h3 class="text-2xl font-extrabold flex items-center gap-3 bg-gradient-to-r from-primary to-info bg-clip-text text-transparent">
                        <div class="avatar placeholder">
                          <div class="bg-gradient-to-br from-primary to-info text-primary-content rounded-full w-12 shadow-lg">
                            <i class="fas fa-globe text-xl"></i>
                          </div>
                        </div>
                        <span>{{ translations.authorized_domains || "Domaines autorisés" }}</span>
                      </h3>
                    </div>
                  
                  <!-- Statistiques compactes -->
                  <div v-if="licenseData" class="mb-6">
                    <div class="stats stats-vertical lg:stats-horizontal shadow-2xl w-full bg-gradient-to-br from-base-200 via-base-300 to-base-200 border-2 border-base-300">
                      <div class="stat py-4 px-6 hover:bg-base-100/50 transition-all duration-300 rounded-lg group">
                        <div class="stat-figure text-primary opacity-90 group-hover:opacity-100 group-hover:scale-110 transition-all duration-300">
                          <div class="avatar placeholder">
                            <div class="bg-gradient-to-br from-primary to-primary-focus text-primary-content rounded-full w-16 shadow-lg">
                              <i class="fas fa-server text-2xl"></i>
                            </div>
                          </div>
                        </div>
                        <div class="stat-title text-sm text-base-content/70 font-semibold mb-1">
                          {{ translations.used_domains || "Utilisés" }}
                        </div>
                        <div class="stat-value text-primary text-4xl font-extrabold drop-shadow-sm">{{ usedDomainsCount }}</div>
                        <div class="stat-desc text-xs text-base-content/60 mt-1">
                          <i class="fas fa-circle text-xs mr-1"></i>
                          {{ translations.currently_used || "Actuellement utilisés" }}
                        </div>
                      </div>
                      <div class="stat py-4 px-6 hover:bg-base-100/50 transition-all duration-300 rounded-lg group">
                        <div class="stat-figure text-info opacity-90 group-hover:opacity-100 group-hover:scale-110 transition-all duration-300">
                          <div class="avatar placeholder">
                            <div class="bg-gradient-to-br from-info to-info-focus text-info-content rounded-full w-16 shadow-lg">
                              <i class="fas fa-chart-line text-2xl"></i>
                            </div>
                          </div>
                        </div>
                        <div class="stat-title text-sm text-base-content/70 font-semibold mb-1">
                          {{ translations.max_domains || "Maximum" }}
                        </div>
                        <div class="stat-value text-info text-4xl font-extrabold drop-shadow-sm">{{ maxDomainsText }}</div>
                        <div class="stat-desc text-xs text-base-content/60 mt-1">
                          <i class="fas fa-circle text-xs mr-1"></i>
                          {{ translations.limit || "Limite de la licence" }}
                        </div>
                      </div>
                      <div class="stat py-4 px-6 hover:bg-base-100/50 transition-all duration-300 rounded-lg group">
                        <div class="stat-figure opacity-90 group-hover:opacity-100 group-hover:scale-110 transition-all duration-300" :class="remainingDomainsClass">
                          <div class="avatar placeholder">
                            <div class="rounded-full w-16 shadow-lg" :class="remainingDomainsClass === 'text-success' ? 'bg-gradient-to-br from-success to-success-focus text-success-content' : remainingDomainsClass === 'text-warning' ? 'bg-gradient-to-br from-warning to-warning-focus text-warning-content' : 'bg-gradient-to-br from-error to-error-focus text-error-content'">
                              <i class="fas fa-check-circle text-2xl"></i>
                            </div>
                          </div>
                        </div>
                        <div class="stat-title text-sm text-base-content/70 font-semibold mb-1">
                          {{ translations.remaining_domains || "Restants" }}
                        </div>
                        <div class="stat-value text-4xl font-extrabold drop-shadow-sm" :class="remainingDomainsClass">{{ remainingDomainsText }}</div>
                        <div class="stat-desc text-xs mt-1" :class="remainingDomainsClass">
                          <i class="fas fa-circle text-xs mr-1"></i>
                          {{ translations.available || "Disponibles" }}
                        </div>
                      </div>
                    </div>
                    
                    <!-- Barre de progression compacte -->
                    <div v-if="maxDomains !== null && maxDomains > 0" class="mt-6 card bg-gradient-to-r from-base-200 to-base-300 shadow-lg border border-base-300">
                      <div class="card-body p-4">
                        <div class="flex justify-between items-center mb-3">
                          <span class="font-bold text-base flex items-center gap-2">
                            <i class="fas fa-tachometer-alt text-primary"></i>
                            {{ translations.usage || "Utilisation" }}
                          </span>
                          <span class="badge badge-lg shadow-md font-bold" :class="progressBarClass === 'progress-success' ? 'badge-success' : progressBarClass === 'progress-warning' ? 'badge-warning' : 'badge-error'">
                            <i class="fas fa-percent mr-1"></i>
                            {{ usagePercentage }}%
                          </span>
                        </div>
                        <progress
                          class="progress h-4 shadow-inner transition-all duration-1000 ease-out"
                          :class="progressBarClass"
                          :value="usedDomainsCount"
                          :max="maxDomains"
                        ></progress>
                        <div class="flex justify-between text-sm font-semibold mt-3">
                          <span class="badge badge-outline badge-md shadow-sm">
                            <i class="fas fa-globe mr-1"></i>
                            {{ usedDomainsCount }} / {{ maxDomains }}
                          </span>
                          <span class="badge badge-outline badge-md shadow-sm" :class="remainingDomainsClass === 'text-success' ? 'badge-success' : remainingDomainsClass === 'text-warning' ? 'badge-warning' : 'badge-error'">
                            <i class="fas fa-hourglass-half mr-1"></i>
                            {{ remainingDomainsText }} {{ translations.remaining || "restants" }}
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Messages d'avertissement compacts -->
                    <div
                      v-if="remainingDomains === 0 && maxDomains !== null"
                      class="alert alert-warning shadow-xl mt-4 py-4 border-2 border-warning/50 animate-pulse"
                    >
                      <i class="fas fa-exclamation-triangle text-2xl"></i>
                      <span class="font-bold text-base">
                        {{ translations.limit_reached || "Limite de domaines atteinte." }}
                      </span>
                    </div>
                    <div
                      v-if="remainingDomains > 0 && remainingDomains <= 2 && maxDomains !== null"
                      class="alert alert-info shadow-xl mt-4 py-4 border-2 border-info/50"
                    >
                      <i class="fas fa-info-circle text-2xl"></i>
                      <span class="font-semibold text-base">
                        {{ translations.limit_warning || "Il ne reste que " + remainingDomains + " domaine(s)." }}
                      </span>
                    </div>
                  </div>
                  
                  <!-- Formulaire compact -->
                  <div v-if="licenseData" class="mb-6">
                    <div class="form-control">
                      <label class="label pb-2">
                        <span class="label-text font-bold text-base flex items-center gap-2">
                          <i class="fas fa-plus-circle text-primary"></i>
                          {{ translations.add_domain || "Ajouter un domaine" }}
                        </span>
                      </label>
                      <div class="input-group">
                        <input
                          type="text"
                          v-model="newDomain"
                          :placeholder="translations.enter_domain || 'exemple.com'"
                          class="input input-bordered flex-1 focus:input-primary focus:ring-2 focus:ring-primary transition-all duration-200"
                          :class="{
                            'input-error focus:ring-error': domainErrorMessage,
                            'input-disabled opacity-50': maxDomains !== null && maxDomains !== -1 && remainingDomains === 0
                          }"
                          @keyup.enter="addDomain"
                          :disabled="maxDomains !== null && maxDomains !== -1 && remainingDomains === 0"
                        />
                        <button
                          @click="addDomain"
                          class="btn btn-primary shadow-md hover:shadow-xl hover:scale-105 transition-all duration-200"
                          :disabled="loadingDomain || !newDomain || (maxDomains !== null && maxDomains !== -1 && remainingDomains === 0)"
                        >
                          <span v-if="loadingDomain" class="loading loading-spinner loading-sm"></span>
                          <i v-else class="fas fa-plus text-lg"></i>
                          <span v-if="!loadingDomain" class="font-semibold">{{ translations.add_domain || "Ajouter" }}</span>
                        </button>
                      </div>
                      <label v-if="domainErrorMessage" class="label py-2 animate-fade-in">
                        <span class="label-text-alt text-error text-sm font-semibold flex items-center gap-2">
                          <i class="fas fa-exclamation-circle animate-pulse"></i>
                          {{ domainErrorMessage }}
                        </span>
                      </label>
                    </div>
                  </div>
                  
                  <!-- Liste des domaines compacte -->
                  <div v-if="licenseData && licenseData.domains && licenseData.domains.length > 0" class="mt-6">
                    <div class="overflow-x-auto shadow-xl rounded-lg border-2 border-base-300">
                      <table class="table table-zebra">
                        <thead class="bg-gradient-to-r from-base-200 to-base-300">
                          <tr>
                            <th class="text-sm font-bold">
                              <i class="fas fa-globe mr-2 text-primary"></i>
                              {{ translations.domain || "Domaine" }}
                            </th>
                            <th class="text-sm font-bold">
                              <i class="fas fa-info-circle mr-2 text-info"></i>
                              {{ translations.status || "Statut" }}
                            </th>
                            <th class="text-sm font-bold text-right">
                              <i class="fas fa-cog mr-2 text-warning"></i>
                              {{ translations.actions || "Actions" }}
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr
                            v-for="(domain, index) in licenseData.domains"
                            :key="index"
                            class="hover:bg-base-200 transition-all duration-200"
                            :class="{
                              'bg-gradient-to-r from-success/20 to-success/10 border-l-4 border-success': domain === licenseData.current_domain,
                            }"
                          >
                            <td>
                              <div class="flex items-center gap-3">
                                <div class="avatar placeholder">
                                  <div class="bg-primary text-primary-content rounded-full w-8">
                                    <i class="fas fa-server text-xs"></i>
                                  </div>
                                </div>
                                <span class="font-mono text-base font-semibold">{{ domain }}</span>
                                <span
                                  v-if="domain === licenseData.current_domain"
                                  class="badge badge-success badge-sm text-xs shadow-md font-medium animate-pulse"
                                >
                                  <i class="fas fa-check-circle mr-1 text-xs"></i>
                                  {{ translations.current_domain || "Actuel" }}
                                </span>
                              </div>
                            </td>
                            <td>
                              <span
                                class="badge badge-sm text-xs shadow-sm font-medium"
                                :class="
                                  domain === licenseData.current_domain
                                    ? 'badge-success'
                                    : 'badge-info'
                                "
                              >
                                <i :class="domain === licenseData.current_domain ? 'fas fa-check-circle mr-1 text-xs' : 'fas fa-circle mr-1 text-xs'"></i>
                                {{
                                  domain === licenseData.current_domain
                                    ? translations.active || "Actif"
                                    : translations.authorized || "Autorisé"
                                }}
                              </span>
                            </td>
                            <td class="text-right">
                              <button
                                v-if="domain !== licenseData.current_domain"
                                @click="confirmRemoveDomain(domain)"
                                class="btn btn-sm btn-error btn-outline shadow-md hover:shadow-xl hover:scale-110 transition-all duration-200"
                                :disabled="loadingDomain"
                              >
                                <i class="fas fa-trash"></i>
                              </button>
                              <span v-else class="badge badge-ghost badge-sm shadow-sm">
                                <i class="fas fa-lock"></i>
                              </span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div v-else-if="licenseData" class="alert shadow-xl mt-6 py-6 border-2 border-base-300">
                    <i class="fas fa-inbox text-3xl text-base-content/40"></i>
                    <span class="text-base font-semibold">{{ translations.no_domains || "Aucun domaine autorisé" }}</span>
                  </div>
                  </div>
                </div>
              </div>

              <!-- Liste des plugins avec collapse -->
              <div v-if="licenseData && licenseData.plugins" class="mt-6 animate-fade-in">
                <div class="card bg-gradient-to-br from-base-100 via-base-100 to-base-200 shadow-2xl border-2 border-primary/20 hover:border-primary/40 transition-all duration-300">
                  <div class="card-body p-0">
                    <!-- En-tête avec collapse -->
                    <div class="collapse collapse-arrow bg-transparent" :class="{ 'collapse-open': pluginsListOpen }">
                      <input type="checkbox" v-model="pluginsListOpen" class="hidden" />
                      <div 
                        class="collapse-title text-lg font-extrabold px-6 py-4 bg-gradient-to-r from-base-200 to-base-300 border-b-2 border-base-300 hover:bg-gradient-to-r hover:from-base-300 hover:to-base-200 transition-all duration-200 cursor-pointer"
                        @click="pluginsListOpen = !pluginsListOpen"
                      >
                        <div class="flex items-center justify-between w-full">
                          <div class="flex items-center gap-3">
                            <div class="avatar placeholder">
                              <div class="bg-gradient-to-br from-primary to-info text-primary-content rounded-full w-10 shadow-lg">
                                <i class="fas fa-plug text-lg"></i>
                              </div>
                            </div>
                            <span class="bg-gradient-to-r from-primary to-info bg-clip-text text-transparent">
                              {{ translations.plugins_list || "Plugins de la licence" }}
                            </span>
                            <span class="badge badge-primary badge-sm text-xs shadow-md font-semibold">
                              {{ Object.keys(licenseData.plugins).length }}
                            </span>
                          </div>
                          
                        </div>
                      </div>
                      <div class="collapse-content px-0 bg-transparent">
                        <div class="p-6">
                          <div class="overflow-x-auto shadow-xl rounded-lg border-2 border-base-300">
                            <table class="table table-zebra">
                              <thead class="bg-gradient-to-r from-base-200 to-base-300">
                                <tr>
                                  <th class="text-sm font-bold">
                                    <i class="fas fa-cube mr-2 text-primary"></i>
                                    {{ translations.addon_name || "Nom du module" }}
                                  </th>
                                  <th class="text-sm font-bold">
                                    <i class="fas fa-check-circle mr-2 text-success"></i>
                                    {{ translations.installed || "Installé" }}
                                  </th>
                                  <th class="text-sm font-bold">
                                    <i class="fas fa-tag mr-2 text-info"></i>
                                    {{ translations.version || "Version" }}
                                  </th>
                                  <th class="text-sm font-bold text-right">
                                    <i class="fas fa-cog mr-2 text-warning"></i>
                                    {{ translations.actions || "Actions" }}
                                  </th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr 
                                  v-for="plugin in licenseData.plugins" 
                                  :key="plugin.product_slug"
                                  class="hover:bg-base-200 transition-all duration-200"
                                  :class="{
                                    'bg-success/5': installed_versions[plugin.product_slug]
                                  }"
                                >
                                  <td>
                                    <div class="flex items-center gap-3">
                                      <div class="avatar placeholder">
                                        <div class="bg-gradient-to-br from-primary to-info text-primary-content rounded-full w-10 shadow-md">
                                          <i class="fas fa-puzzle-piece text-sm"></i>
                                        </div>
                                      </div>
                                      <div>
                                        <div class="font-bold text-base text-primary">{{ plugin.product_name }}</div>
                                        <div class="text-xs text-base-content/60 font-mono mt-1">{{ plugin.product_slug }}</div>
                                      </div>
                                    </div>
                                  </td>
                                  <td>
                                    <span 
                                      class="badge badge-sm text-xs shadow-sm font-medium" 
                                      :class="installed_versions[plugin.product_slug] ? 'badge-success' : 'badge-ghost'"
                                    >
                                      <i :class="installed_versions[plugin.product_slug] ? 'fas fa-check-circle mr-1 text-xs' : 'fas fa-times-circle mr-1 text-xs'"></i>
                                      {{ installed_versions[plugin.product_slug] ? (translations.installed || "Installé") : (translations.not_installed || "Non installé") }}
                                    </span>
                                  </td>
                                  <td>
                                    <div class="flex items-center gap-2">
                                      <span class="font-mono text-sm font-semibold" :class="installed_versions[plugin.product_slug] ? 'text-base-content' : 'text-base-content/40'">
                                        {{ installed_versions[plugin.product_slug] || '-' }}
                                      </span>
                                      <span 
                                        v-if="updatesAvailable[plugin.product_slug]" 
                                        class="badge badge-warning badge-sm text-xs shadow-sm font-medium animate-pulse"
                                      >
                                        <i class="fas fa-exclamation-triangle mr-1 text-xs"></i>
                                        Mise à jour
                                      </span>
                                    </div>
                                  </td>
                                  <td class="text-right">
                                    <div class="flex justify-end gap-2">
                                      <button
                                        v-if="installed_versions[plugin.product_slug]"
                                        @click="checkUpdatePlugin(plugin.product_slug, installed_versions[plugin.product_slug])"
                                        class="btn btn-sm btn-info btn-outline shadow-md hover:shadow-xl hover:scale-110 transition-all duration-200"
                                        :title="translations.check_update || 'Vérifier les mises à jour'"
                                      >
                                        <i class="fas fa-sync"></i>
                                      </button>
                                      <button
                                        v-if="updatesAvailable[plugin.product_slug] || !installed_versions[plugin.product_slug]"
                                        @click="installUpdatePlugin(plugin.product_slug, updatesAvailable[plugin.product_slug])"
                                        class="btn btn-sm btn-primary shadow-md hover:shadow-xl hover:scale-110 transition-all duration-200"
                                        :title="updatesAvailable[plugin.product_slug] ? (translations.update || 'Mettre à jour') : (translations.install || 'Installer')"
                                      >
                                        <i :class="updatesAvailable[plugin.product_slug] ? 'fas fa-arrow-up' : 'fas fa-download'"></i>
                                        <span class="ml-1">{{ updatesAvailable[plugin.product_slug] ? (translations.update || "Mettre à jour") : (translations.install || "Installer") }}</span>
                                      </button>
                                    </div>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              </div>
            </div>
            </div>
        </Card>
      </div>
</template>

<script>
import Card from "@/components/Card.vue";
import { VueEditor } from "vue3-editor";
import RemoveModal from "@/components/RemoveAlert.vue";

export default {
  name: "Settings",
  components: {
    Card,
    VueEditor,
    RemoveModal,
  },
  data() {
    return {
      loading: false,
      selectedTab: 1,
      pluginsListOpen: false,
      form: {
        company_name: "",
        company_address: "",
        postal_code: "",
        city: "",
        country: "",
        company_email: "",
        company_phone: "",
        mobile_phone: "",
        fax: "",
        logo_mentions_active: "",
        logo_url: "",
        logo_path: "",
        default_currency: "",
        currency_position: "",
        vat_active: "",
        default_vat: "",
        date_format: "",
        logo_width: "",
        logo_mentions: "",
        invoice_color: "",
        invoice_prefix: "",
        active_disbursements: "",
        show_phone: "",
        show_email: "",
        show_siren: "",
        show_tax_number: "",
        show_watermark: "",
        show_watermark_only_paid: "",
        invoice_footer: "",
        invoice_terms: "",
        credit_color: "",
        credit_prefix: "",
        credit_footer: "",
        credit_terms: "",
        quote_color: "",
        quote_prefix: "",
        quote_footer: "",
        quote_terms: "",
        easy_compta_planning_addon_active: "",
        easy_compta_email_addon_active: "",
        easy_compta_payment_addon_active: "",
        easy_compta_signature_addon_active: "",
        easy_compta_stats_addon_active: "",
        easy_compta_qrcode_addon_active: "",
        easy_compta_stripe_secret_api: "",
        email_quote_subject: "",
        email_invoice_subject: "",
        remind_invoice_subject: "",
        remind_invoice_content: "",
        email_quote_content: "",
        email_invoice_content: "",
        email_create_account_subject: "",
        email_create_account_content: "",
        easycompta_siret_token_api: "",
      },
      articles: [],
      categories: [],
      currencies: [],
      vats: [],
      expenses: [],
      planning: [],
      payments: [],
      logoPreviewUrl: "",
      previewWidth: "",
      showArticleModal: false,
      showCurrencyModal: false,
      showVATModal: false,
      showPaymentModal: false,
      showExpenseModal: false,
      showPlanningModal: false,
      currencyForm: {
        id: null,
        name: "",
        symbol: "",
      },
      articleForm: {
        id: null,
        ref: "",
        name: "",
        description: "",
        unit_price: "",
      },
      vatForm: {
        id: null,
        description: "",
        rate: "",
      },
      paymentForm: {
        id: null,
        method_name: "",
      },
      expenseForm: {
        id: null,
        name: "",
      },
      planningForm: {
        id: null,
        name: "",
        background: "",
        color: "",
      },
      showRemoveModal: false,
      deleteType: null,
      selectedId: null,
      editingArticle: false,
      editingCurrency: false,
      editingVAT: false,
      editingExpense: false,
      editingPayment: false,
      editingPlanning: false,
      activeTabEmail: "tab1",
      activeTabUsers: "user1",
      license_key: "",
      loadingLicense: false,
      licenseData: null,
      installed_versions: {},
      updatesAvailable: {},
      errorMessage: "",
      newDomain: "",
      loadingDomain: false,
      domainErrorMessage: "",
      domainToRemove: null,
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
      toolbarOptions: [
        ["bold", "italic", "underline", "strike"],
        ["link"],
        [{ list: "ordered" }, { list: "bullet" }],
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
        [{ color: [] }, { background: [] }],
        [{ align: [] }],
        [{ align: "right" }, { align: "center" }, { align: "justify" }],
        ["clean"],
        ["html"],
      ],
    };
  },
  methods: {
    setActiveTab(tab) {
      this.activeTabEmail = tab;
    },
    setActiveTabUsers(tab) {
      this.activeTabUsers = tab;
    },
    selectTab(tab) {
      this.selectedTab = tab;
      window.location.hash = `tab${tab}`;
    },
    checkHash() {
      const hash = window.location.hash;
      if (hash) {
        const tab = parseInt(hash.replace("#tab", ""));
        if (!isNaN(tab)) {
          this.selectedTab = tab;
        }
      }
    },
    tabClass(tab) {
      return this.selectedTab === tab ? "tab tab-active" : "tab";
    },
    async fetchSettings() {
      try {
        this.loading = true;
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/settings/get",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        this.loading = false;
        if (response.ok) {
          const settings = await response.json();
          this.form = { ...this.form, ...settings };
          this.logoPreviewUrl = settings.logo_url || "";
          if (this.form.easy_compta_planning_addon_active == 1) {
            this.fetchPlanningCat();
          }
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.loading = false;
        this.showToast(error.message, "alert-error");
      }
    },
    async handleSubmit() {
      try {
        this.loading = true;
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/settings/save",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify(this.form),
          }
        );

        this.loading = false;
        if (response.ok) {
          const result = await response.json();
          this.showToast(result, "alert-success");
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.loading = false;
        this.showToast(error.message, "alert-error");
      }
    },
    async fetchArticles() {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/settings/articles",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.articles = data.articles;
          this.categories = data.categories;
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async fetchCurrencies() {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/settings/currencies",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.currencies = await response.json();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async fetchVATs() {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/settings/vats",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.vats = await response.json();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async fetchPaymentsMethods() {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/settings/payments-methods",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.payments = await response.json();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async fetchExpensesCat() {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/settings/expenses-cat",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.expenses = await response.json();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async fetchPlanningCat() {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/settings/planning-cat",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.planning = await response.json();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async handleLogoUpload(event) {
      const file = event.target.files[0];
      if (file) {
        const formData = new FormData();
        formData.append("logo", file);

        try {
          const response = await fetch(
            "/wp-json/my-easy-compta/v1/settings/upload-logo",
            {
              method: "POST",
              headers: {
                "X-WP-Nonce": myEasyComptaAdmin.nonce,
              },
              body: formData,
            }
          );

          if (response.ok) {
            const result = await response.json();
            this.form.logo_url = result.url;
            this.form.logo_path = result.path;
            this.logoPreviewUrl = result.url;
            this.showToast("Logo uploaded successfully", "alert-success");
          } else {
            const error = await response.json();
            this.showToast(error.message, "alert-error");
          }
        } catch (error) {
          this.showToast(error.message, "alert-error");
        }
      }
    },
    async addArticle() {
      this.articleForm = {
        id: null,
        ref: "",
        name: "",
        description: "",
        price: "",
      };
      this.editingArticle = false;
      this.showArticleModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_article").showModal();
      });
    },
    async addCurrency() {
      this.currencyForm = {
        id: null,
        name: "",
        symbol: "",
      };
      this.editingCurrency = false;
      this.showCurrencyModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_currency").showModal();
      });
    },
    async deleteArticle(id) {
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/settings/articles/${id}`,
          {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.articles = this.articles.filter((article) => article.id !== id);
          this.showToast(
            this.translations.deleted_successfully,
            "alert-success"
          );
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async deleteCategory(id) {
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/settings/category/${id}`,
          {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.categories = this.categories.filter(
            (category) => category.id !== id
          );
          this.showToast(
            this.translations.deleted_successfully,
            "alert-success"
          );
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },

    async editArticle(id) {
      const article = this.articles.find((art) => art.id === id);
      this.articleForm = { ...article };
      this.editingArticle = true;
      this.showArticleModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_article").showModal();
      });
    },

    async editCurrency(id) {
      const currency = this.currencies.find((cur) => cur.id === id);
      this.currencyForm = { ...currency };
      this.editingCurrency = true;
      this.showCurrencyModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_currency").showModal();
      });
    },
    async deleteCurrency(id) {
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/settings/currencies/${id}`,
          {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.currencies = this.currencies.filter(
            (currency) => currency.id !== id
          );
          this.showToast("Currency deleted successfully", "alert-success");
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async saveArticle() {
      const method = this.editingArticle ? "PUT" : "POST";
      const url = this.editingArticle
        ? `/wp-json/my-easy-compta/v1/settings/articles/${this.articleForm.id}`
        : "/wp-json/my-easy-compta/v1/settings/articles";

      try {
        const response = await fetch(url, {
          method,
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
          body: JSON.stringify(this.articleForm),
        });

        if (response.ok) {
          const result = await response.json();
          if (this.editingArticle) {
            const indexExp = this.articles.findIndex(
              (article) => article.id === result.id
            );
            this.articles[indexExp] = result;
          } else {
            this.articles.push(result);
          }
          this.showToast(
            `Article ${this.editingArticle ? "updated" : "added"} successfully`,
            "alert-success"
          );
          this.closeArticleModal();
          this.fetchArticles();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async saveCurrency() {
      const method = this.editingCurrency ? "PUT" : "POST";
      const url = this.editingCurrency
        ? `/wp-json/my-easy-compta/v1/settings/currencies/${this.currencyForm.id}`
        : "/wp-json/my-easy-compta/v1/settings/currencies";

      try {
        const response = await fetch(url, {
          method,
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
          body: JSON.stringify(this.currencyForm),
        });

        if (response.ok) {
          const result = await response.json();
          if (this.editingCurrency) {
            const index = this.currencies.findIndex(
              (cur) => cur.id === result.id
            );
            this.currencies[index] = result;
          } else {
            this.currencies.push(result);
          }
          this.showToast(
            `Currency ${
              this.editingCurrency ? "updated" : "added"
            } successfully`,
            "alert-success"
          );
          this.closeCurrencyModal();
          this.fetchCurrencies();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async addVAT() {
      this.vatForm = {
        id: null,
        description: "",
        rate: "",
      };
      this.editingVAT = false;
      this.showVATModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_vat").showModal();
      });
    },
    async editVAT(id) {
      const vat = this.vats.find((vat) => vat.id === id);
      this.vatForm = { ...vat };
      this.editingVAT = true;
      this.showVATModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_vat").showModal();
      });
    },
    async deleteVAT(id) {
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/settings/vats/${id}`,
          {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.vats = this.vats.filter((vat) => vat.id !== id);
          this.showToast("VAT deleted successfully", "alert-success");
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async saveVAT() {
      const method = this.editingVAT ? "PUT" : "POST";
      const url = this.editingVAT
        ? `/wp-json/my-easy-compta/v1/settings/vats/${this.vatForm.id}`
        : "/wp-json/my-easy-compta/v1/settings/vats";

      try {
        const response = await fetch(url, {
          method,
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
          body: JSON.stringify(this.vatForm),
        });

        if (response.ok) {
          const result = await response.json();
          if (this.editingVAT) {
            const index = this.vats.findIndex((vat) => vat.id === result.id);
            this.vats[index] = result;
          } else {
            this.vats.push(result);
          }
          this.showToast(
            `VAT ${this.editingVAT ? "updated" : "added"} successfully`,
            "alert-success"
          );
          this.closeVATModal();
          this.fetchVATs();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async addPayment() {
      this.paymentForm = {
        id: null,
        method_name: "",
      };
      this.editingPayment = false;
      this.showPaymentModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_payments").showModal();
      });
    },
    async editPayment(id) {
      const payment = this.payments.find((payment) => payment.id === id);
      this.paymentForm = { ...payment };
      this.editingPayment = true;
      this.showPaymentModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_payments").showModal();
      });
    },
    async deletePayment(id) {
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/settings/payments-methods/${id}`,
          {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.payments = this.payments.filter((payment) => payment.id !== id);
          this.showToast(
            "Payment method deleted successfully",
            "alert-success"
          );
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },

    async savePayment() {
      const method = this.editingPayment ? "PUT" : "POST";
      const url = this.editingPayment
        ? `/wp-json/my-easy-compta/v1/settings/payments-methods/${this.paymentForm.id}`
        : "/wp-json/my-easy-compta/v1/settings/payments-methods";

      try {
        const response = await fetch(url, {
          method,
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
          body: JSON.stringify(this.paymentForm),
        });

        if (response.ok) {
          const result = await response.json();
          if (this.editingPayment) {
            const index = this.payments.findIndex(
              (payment) => payment.id === result.id
            );
            this.payments[index] = result;
          } else {
            this.payments.push(result);
          }
          this.showToast(
            `Payment method ${
              this.editingPayment ? "updated" : "added"
            } successfully`,
            "alert-success"
          );
          this.closePaymentModal();
          this.fetchPaymentsMethods();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },

    async addExpCat() {
      this.expenseForm = {
        id: null,
        name: "",
      };
      this.editingExpense = false;
      this.showExpenseModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_expenses").showModal();
      });
    },
    async editExpCat(id) {
      const expense = this.expenses.find((expense) => expense.id === id);
      this.expenseForm = { ...expense };
      this.editingExpense = true;
      this.showExpenseModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_expenses").showModal();
      });
    },
    async deleteExpCat(id) {
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/settings/expenses-categories/${id}`,
          {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.expenses = this.expenses.filter((expense) => expense.id !== id);
          this.showToast(
            "Expense category deleted successfully",
            "alert-success"
          );
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async saveExpCat() {
      const method = this.editingExpense ? "PUT" : "POST";
      const url = this.editingExpense
        ? `/wp-json/my-easy-compta/v1/settings/expenses-categories/${this.expenseForm.id}`
        : "/wp-json/my-easy-compta/v1/settings/expenses-categories";

      try {
        const response = await fetch(url, {
          method,
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
          body: JSON.stringify(this.expenseForm),
        });

        if (response.ok) {
          const result = await response.json();
          if (this.editingExpense) {
            const indexExp = this.expenses.findIndex(
              (expense) => expense.id === result.id
            );
            this.expenses[indexExp] = result;
          } else {
            this.expenses.push(result);
          }
          this.showToast(
            `Expense category ${
              this.editingExpense ? "updated" : "added"
            } successfully`,
            "alert-success"
          );
          this.closeExpenseModal();
          this.fetchExpensesCat();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async addPlanningCat() {
      this.planningForm = {
        id: null,
        name: "",
      };
      this.editingPlanning = false;
      this.showPlanningModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_planning").showModal();
      });
    },
    async editPlanningCat(id) {
      const planning = this.planning.find((p) => p.id === id);
      this.planningForm = { ...planning };
      this.editingPlanning = true;
      this.showPlanningModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_planning").showModal();
      });
    },
    async deletePlanningCat(id) {
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/settings/planning-categories/${id}`,
          {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          this.planning = this.planning.filter((p) => p.id !== id);
          this.showToast(
            "Planning category deleted successfully",
            "alert-success"
          );
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async savePlanningCat() {
      this.loading = true;
      const method = this.editingPlanning ? "PUT" : "POST";
      const url = this.editingPlanning
        ? `/wp-json/my-easy-compta/v1/settings/planning-categories/${this.planningForm.id}`
        : "/wp-json/my-easy-compta/v1/settings/planning-categories";

      try {
        const response = await fetch(url, {
          method,
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
          body: JSON.stringify(this.planningForm),
        });

        if (response.ok) {
          const result = await response.json();
          if (this.editingPlanning) {
            const indexPlanning = this.planning.findIndex(
              (p) => p.id === result.id
            );
            this.planning[indexPlanning] = result;
            this.loading = false;
          } else {
            this.planning.push(result);
            this.loading = false;
          }
          this.showToast(
            `Planning category ${
              this.editingPlanning ? "updated" : "added"
            } successfully`,
            "alert-success"
          );
          this.closePlanningModal();
          this.fetchPlanningCat();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    closeCurrencyModal() {
      this.showCurrencyModal = false;
    },
    closeArticleModal() {
      this.showArticleModal = false;
    },
    closeVATModal() {
      this.showVATModal = false;
    },
    closePaymentModal() {
      this.showPaymentModal = false;
    },
    closeExpenseModal() {
      this.showExpenseModal = false;
    },
    closePlanningModal() {
      this.showPlanningModal = false;
    },
    updatePreviewWidth() {
      this.previewWidth = this.form.logo_width + "px";
    },
    updateVatActive(event) {
      this.form.vat_active = event.target.checked ? 1 : 0;
    },
    updateLogoMentionsActive(event) {
      this.form.logo_mentions_active = event.target.checked ? 1 : 0;
    },
    updateEmailLogsActive(event) {
      this.form.email_log_active = event.target.checked ? 1 : 0;
    },
    updateFormField(event, fieldName) {
      this.form[fieldName] = event.target.checked ? 1 : 0;
    },
    showToast(message, type) {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.visible = true;
      setTimeout(() => {
        this.toast.visible = false;
      }, 3000);
    },
    handleDeletion(type, id) {
      const deletionFunction = this.getDeletionFunction(type);
      if (deletionFunction) {
        deletionFunction(id);
      } else {
        this.showToast("error", "alert-error");
      }
    },
    delete_item(type, id) {
      this.deleteType = type;
      this.selectedId = id;
      modal_settings_remove.showModal();
      this.showRemoveModal = true;
    },
    getDeletionFunction(type) {
      switch (type) {
        case "licence":
          return this.confirmLicense;
        case "vat":
          return this.deleteVAT;
        case "currency":
          return this.deleteCurrency;
        case "expense":
          return this.deleteExpCat;
        case "payment":
          return this.deletePayment;
        case "planning":
          return this.deletePlanningCat;
        case "article":
          return this.deleteArticle;
        case "category_article":
          return this.deleteCategory;
        default:
          return null;
      }
    },
    async checkLicense() {
      this.loading = true;
      this.loadingLicense = true;
      this.errorMessage = "";
      this.licenseData = null;

      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/license/validate-license",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              license_key: this.license_key,
            }),
          }
        );

        const data = await response.json();

        if (data.valid) {
          this.licenseData = data;
          await this.storeLicense(data);
          location.reload();
        } else {
          this.errorMessage = data.message;
        }
      } catch (error) {
        this.errorMessage = "An error occurred while validating the license.";
      } finally {
        this.loadingLicense = false;
      }
    },
    async refresh_licence() {
      this.loading = true;
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/license/refresh-license",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        const data = await response.json();
        if (data.valid) {
          // Mettre à jour les données de licence localement
          this.licenseData = data.license_data;
          if (!this.licenseData.domains) {
            this.licenseData.domains = [];
          }
          if (!this.licenseData.current_domain && this.licenseData.domain) {
            this.licenseData.current_domain = this.licenseData.domain;
          }
          this.showToast(
            this.translations.license_refreshed || "Licence rafraîchie avec succès",
            "alert-success"
          );
          this.loading = false;
        } else {
          this.errorMessage = data.message;
          this.loading = false;
        }
      } catch (error) {
        this.errorMessage = "An error occurred while refreshing the license.";
        this.loading = false;
      }
    },
    async storeLicense(data) {
      try {
        await fetch("/wp-json/my-easy-compta/v1/license/store-license", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
          body: JSON.stringify({
            license_key: this.license_key,
            license_data: data,
          }),
        });
      } catch (error) {
        this.errorMessage = "An error occurred while storing the license.";
        this.loading = false;
      }
    },
    async loadLicenseDetails() {
      this.loading = true;
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/license/check-license",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );
        if (response.ok) {
          const data = await response.json();
          if (data.valid) {
            this.licenseData = data.license_data;
            this.installed_versions = data.installed_versions;
            // S'assurer que licenseData.domains existe
            if (!this.licenseData.domains) {
              this.licenseData.domains = [];
            }
            // S'assurer que current_domain existe
            if (!this.licenseData.current_domain && this.licenseData.domain) {
              this.licenseData.current_domain = this.licenseData.domain;
            }
            this.license_key =
              "****-****-****-****-****" +
              this.license_key.substr(this.license_key.length - 4);
            this.loading = false;
          }
        } else {
          console.error("Failed to load license details");
          this.loading = false;
        }
      } catch (error) {
        console.error("Error loading license details", error);
        this.loading = false;
      }
    },
    async confirmLicense() {
      this.loading = true;
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/license/delete-license`,
          {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.showToast(data.message, "alert-success");
          this.licenseData = "";
          this.license_key = "";
          location.reload();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    async addDomain() {
      if (!this.newDomain || !this.newDomain.trim()) {
        this.domainErrorMessage = this.translations.domain_required || "Le domaine est requis";
        return;
      }

      this.loadingDomain = true;
      this.domainErrorMessage = "";

      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/license/add-domain",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              domain: this.newDomain.trim(),
            }),
          }
        );

        const data = await response.json();

        if (response.ok && data.success) {
          this.showToast(
            data.message || this.translations.domain_added || "Domaine ajouté avec succès",
            "alert-success"
          );
          this.newDomain = "";
          // Rafraîchir les données de licence
          await this.loadLicenseDetails();
        } else {
          this.domainErrorMessage =
            data.message || this.translations.domain_add_failed || "Échec de l'ajout du domaine";
          this.showToast(
            data.message || this.translations.domain_add_failed || "Échec de l'ajout du domaine",
            "alert-error"
          );
        }
      } catch (error) {
        this.domainErrorMessage =
          this.translations.error_occurred || "Une erreur s'est produite";
        this.showToast(
          this.translations.error_occurred || "Une erreur s'est produite",
          "alert-error"
        );
      } finally {
        this.loadingDomain = false;
      }
    },
    confirmRemoveDomain(domain) {
      this.domainToRemove = domain;
      // Utiliser le modal de confirmation existant ou créer une confirmation simple
      if (
        confirm(
          (this.translations.confirm_remove_domain || "Êtes-vous sûr de vouloir supprimer le domaine") +
            " " +
            domain +
            "?"
        )
      ) {
        this.removeDomain(domain);
      }
      this.domainToRemove = null;
    },
    async removeDomain(domain) {
      if (!domain) {
        return;
      }

      this.loadingDomain = true;
      this.domainErrorMessage = "";

      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/license/remove-domain",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              domain: domain,
            }),
          }
        );

        const data = await response.json();

        if (response.ok && data.success) {
          this.showToast(
            data.message || this.translations.domain_removed || "Domaine supprimé avec succès",
            "alert-success"
          );
          // Rafraîchir les données de licence
          await this.loadLicenseDetails();
        } else {
          this.domainErrorMessage =
            data.message || this.translations.domain_remove_failed || "Échec de la suppression du domaine";
          this.showToast(
            data.message || this.translations.domain_remove_failed || "Échec de la suppression du domaine",
            "alert-error"
          );
        }
      } catch (error) {
        this.domainErrorMessage =
          this.translations.error_occurred || "Une erreur s'est produite";
        this.showToast(
          this.translations.error_occurred || "Une erreur s'est produite",
          "alert-error"
        );
      } finally {
        this.loadingDomain = false;
      }
    },

    async checkUpdatePlugin(pluginSlug, currentVersion) {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/license/check-update",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              plugin_slug: pluginSlug,
              current_version: currentVersion,
            }),
          }
        );

        const data = await response.json();

        if (data.success) {
          if (data.update_available) {
            this.showToast(
              this.translations.update_available + " " + data.new_version,
              "alert-success"
            );
            this.updatesAvailable[pluginSlug] = data.update_available;
          } else {
            this.showToast(
              this.translations.no_update_available,
              "alert-error"
            );
          }
        } else {
          this.showToast(
            this.translations.failed_update_available,
            "alert-error"
          );
        }
      } catch (error) {
        console.error("Error checking for plugin update:", error);
        this.showToast(
          this.translations.failed_update_available,
          "alert-error"
        );
      }
    },
    async installUpdatePlugin(plugin_slug) {
      try {
        // Afficher un message de chargement
        this.showToast(
          this.translations.installing || "Installation en cours...",
          "alert-info"
        );

        const response = await fetch(
          "/wp-json/my-easy-compta/v1/license/download-update",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              plugin_slug: plugin_slug,
            }),
          }
        );

        const data = await response.json();

        if (data.success) {
          const actionMessage = data.action === 'installed' 
            ? (this.translations.plugin_installed || "Plugin installé avec succès")
            : (this.translations.plugin_updated || "Plugin mis à jour avec succès");
          
          this.showToast(actionMessage, "alert-success");
          
          // Rafraîchir la liste des plugins installés
          await this.loadLicenseDetails();
          
          // Rafraîchir la page après un court délai pour voir le message
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          const errorMessage = data.message || data.code || this.translations.error || "Une erreur est survenue";
          this.showToast(errorMessage, "alert-error");
        }
      } catch (error) {
        console.error("Error installing plugin:", error);
        this.showToast(
          this.translations.error || "Erreur lors de l'installation",
          "alert-error"
        );
      }
    },
  },

  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
    // Nombre de domaines utilisés
    usedDomainsCount() {
      if (!this.licenseData || !this.licenseData.domains) {
        return 0;
      }
      return this.licenseData.domains.length;
    },
    // Nombre maximum de domaines autorisés
    maxDomains() {
      if (!this.licenseData) {
        return null;
      }
      // L'API peut retourner max_domains, max_sites, ou domain_limit
      return (
        this.licenseData.max_domains ||
        this.licenseData.max_sites ||
        this.licenseData.domain_limit ||
        null
      );
    },
    // Texte pour afficher le maximum (illimité si null ou -1)
    maxDomainsText() {
      if (this.maxDomains === null || this.maxDomains === -1) {
        return this.translations.unlimited || "Illimité";
      }
      return this.maxDomains.toString();
    },
    // Nombre de domaines restants
    remainingDomains() {
      if (this.maxDomains === null || this.maxDomains === -1) {
        return -1; // -1 signifie illimité
      }
      const remaining = this.maxDomains - this.usedDomainsCount;
      return Math.max(0, remaining);
    },
    // Texte pour afficher les domaines restants
    remainingDomainsText() {
      if (this.remainingDomains === -1) {
        return this.translations.unlimited || "Illimité";
      }
      return this.remainingDomains.toString();
    },
    // Classe CSS pour le nombre de domaines restants
    remainingDomainsClass() {
      if (this.remainingDomains === -1) {
        return "text-success";
      }
      if (this.remainingDomains === 0) {
        return "text-error";
      }
      if (this.remainingDomains <= 2) {
        return "text-warning";
      }
      return "text-success";
    },
    // Pourcentage d'utilisation
    usagePercentage() {
      if (this.maxDomains === null || this.maxDomains === -1 || this.maxDomains === 0) {
        return 0;
      }
      return Math.round((this.usedDomainsCount / this.maxDomains) * 100);
    },
    // Classe CSS pour la barre de progression (DaisyUI)
    progressBarClass() {
      const percentage = this.usagePercentage;
      if (percentage >= 100) {
        return "progress-error";
      }
      if (percentage >= 80) {
        return "progress-warning";
      }
      return "progress-success";
    },
  },
  beforeUnmount() {
    window.removeEventListener("hashchange", this.checkHash);
  },
  mounted() {
    this.checkHash();
    window.addEventListener("hashchange", this.checkHash);
    this.fetchSettings();
    this.fetchArticles();
    this.fetchCurrencies();
    this.fetchVATs();
    this.fetchPaymentsMethods();
    this.fetchExpensesCat();
    this.loadLicenseDetails();
  },
};
</script>
