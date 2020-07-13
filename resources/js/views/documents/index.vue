<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Comprobantes</span></li>
            </ol>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading">
                        <th>#</th>
                        <th class="text-center">Fecha Emisión</th>
                        <th colspan="2">Cliente</th>
                        <th>Tipo</th>
                        <th>Número</th>
                        <th class="text-right">T.Gratuito</th>
                        <th class="text-right">T.Inafecto</th>
                        <th class="text-right">T.Exonerado</th>
                        <th class="text-right">T.Gravado</th>
                        <th class="text-right">T.Igv</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Descargas</th>
                    <tr>
                    <tr slot-scope="{ index, row }" :class="{'text-danger': (row.state_type_id === '11')}">
                        <td>{{ index }}</td>
                        <td class="text-center">{{ row.date_of_issue }}</td>
                        <td>{{ row.customer_name }}</td>
                        <td>{{ row.customer_number }}</td>
                        <td>{{ row.document_type_short }}<br/>
                        <td>{{ row.number }}<br/>
                            <small v-if="row.affected_document" v-text="row.affected_document"></small>
                        </td>
                        <td class="text-right">{{ row.total_free }}</td>
                        <td class="text-right">{{ row.total_unaffected }}</td>
                        <td class="text-right">{{ row.total_exonerated }}</td>
                        <td class="text-right">{{ row.total_taxed }}</td>
                        <td class="text-right">{{ row.total_igv }}</td>
                        <td class="text-right">{{ row.total }}</td>
                        <td class="text-center">
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickDownload(row.download_external_xml)">XML</button>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickDownload(row.download_external_pdf)">PDF</button>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickDownload(row.download_external_cdr)">CDR</button>
                        </td>
                    </tr>
                </data-table>
            </div>
        </div>
    </div>
</template>

<script>

    import DataTable from '../../components/DataTable.vue'

    export default {
        components: {DataTable},
        data() {
            return {
                resource: 'documents',
                recordId: null,
            }
        },
        created() {
        },
        methods: {
            clickDownload(download) {
                window.open(download, '_blank');
            },
        }
    }
</script>
